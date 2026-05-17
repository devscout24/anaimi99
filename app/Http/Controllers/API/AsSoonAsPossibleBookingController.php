<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Models\BookingItemManage;
use App\Models\BookingTimeMange;
use App\Models\CommissionSetting;
use App\Models\Payment;
use App\Models\ScheduleTimeManage;
use App\Models\ServicePrice;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class AsSoonAsPossibleBookingController extends Controller
{
    use ApiResponse;

    public function asSoonAsPossibleBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'customer_id' => 'required|exists:users,id',
            'payment_type' => 'required|in:online,onsite,cod',
            'slot_id' => 'required|array',
            'slot_id.*' => 'required|exists:schedule_time_manages,id',
            'service_id' => 'required|array',
            'service_id.*' => 'required|exists:services,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:1',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', $validator->errors());
        }

        if (count($request->service_id) != count($request->price) || count($request->service_id) != count($request->quantity)) {
            return $this->error('Service, quantity, and price count mismatch');
        }

        $customer = User::find($request->customer_id);
        if (!$customer->latitude || !$customer->longitude) {
            return $this->error('Customer location not found. Please update your profile.');
        }

        DB::beginTransaction();

        try {
            $requestedSlots = $request->slot_id;
            $date = Carbon::parse($request->date)->format('Y-m-d');

            // 1. Calculate Total Required Minutes
            $totalRequiredMinutes = 0;
            foreach ($request->service_id as $index => $serviceId) {
                $servicePrice = ServicePrice::where('service_id', $serviceId)->first();
                if (!$servicePrice) {
                    throw new \Exception("Service price not found for service ID: {$serviceId}");
                }

                $qty = $request->quantity[$index];
                $totalRequiredMinutes += ($servicePrice->time_duration * $qty);
            }

            // 2. Total Slot Minutes
            $totalSlotMinutes = 0;
            $slots = ScheduleTimeManage::whereIn('id', $requestedSlots)
                ->orderBy('scheduled_start_time')
                ->get();

            if ($slots->count() !== count($requestedSlots)) {
                throw new \Exception('Invalid slots provided.');
            }

            foreach ($slots as $slot) {
                $start = Carbon::parse($slot->scheduled_start_time);
                $end = Carbon::parse($slot->scheduled_end_time);
                $totalSlotMinutes += $start->diffInMinutes($end);
            }

            if ($totalSlotMinutes < $totalRequiredMinutes) {
                throw new \Exception("Selected slots cover {$totalSlotMinutes} minutes, but services require {$totalRequiredMinutes} minutes.");
            }

            // 3. Find Nearest Available Home Barber
            $userLat = (float) $customer->latitude;
            $userLng = (float) $customer->longitude;
            $radius = 50; // KM

            $distanceSql = '( 6371 * ACOS( COS( RADIANS(?) ) * COS( RADIANS(latitude) ) *
                              COS( RADIANS(longitude) - RADIANS(?) ) +
                              SIN( RADIANS(?) ) * SIN( RADIANS(latitude) ) ) )';

            $query = User::selectRaw("users.*, {$distanceSql} AS distance", [$userLat, $userLng, $userLat])
                ->where('role', 'home_barbar')
                ->where('block_status', 'unblock')
                ->where('availability', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw("{$distanceSql} <= ?", [$userLat, $userLng, $userLat, $radius]);

            // Availability filters
            $blockedBarbers = SlotBlockBarber::whereDate('block_date', $date)
                ->whereIn('slot_id', $requestedSlots)
                ->where('status', 'active')
                ->pluck('barber_id')
                ->filter()
                ->toArray();

            $bookedBarbers = BookingTimeMange::whereDate('date', $date)
                ->whereIn('schedule_id', $requestedSlots)
                ->where('status', 'active')
                ->pluck('barber_id')
                ->filter()
                ->toArray();

            $unavailable = array_unique(array_merge($blockedBarbers, $bookedBarbers));

            if (!empty($unavailable)) {
                $query->whereNotIn('id', $unavailable);
            }

            $freeBarber = $query->orderBy('distance', 'asc')->first();

            if (!$freeBarber) {
                throw new \Exception('No barber available nearby.');
            }

            // 4. Create Booking
            $booking = new Booking();
            $booking->customer_id = $request->customer_id;
            $booking->barber_id = $freeBarber->id;
            $booking->subtotal = $request->subtotal ?? array_sum($request->price);
            $booking->tax = $request->tax ?? 0;
            $booking->total_price = $request->total_price ?? array_sum($request->price);
            $booking->total_service_quantity = count($request->service_id);
            $booking->payment_type = $request->payment_type;
            $booking->booking_type = 'as_soon_possible';
            $booking->payment_status = 'pending';
            $booking->status = 'search_barber';
            $booking->last_assigned_at = now();
            $booking->booking_date = $date;

            // Commission
            $commission = CommissionSetting::calculateCommission((float) $booking->total_price, 'as_soon_possible');
            $booking->commission_rate = $commission['commission_rate'];
            $booking->admin_commission = $commission['admin_commission'];
            $booking->provider_earnings = $commission['provider_earnings'];

            $booking->save();

            // 5. Save Items and Slots
            foreach ($request->slot_id as $slotId) {
                BookingTimeMange::create([
                    'booking_id' => $booking->id,
                    'barber_id' => $freeBarber->id,
                    'schedule_id' => $slotId,
                    'date' => $date,
                    'status' => 'active',
                ]);
            }

            foreach ($request->service_id as $index => $serviceId) {
                $itemPrice = $request->price[$index] ?? 0;
                $itemQuantity = $request->quantity[$index] ?? 1;

                BookingItemManage::create([
                    'booking_id' => $booking->id,
                    'service_id' => $serviceId,
                    'quantity' => $itemQuantity,
                    'price' => $itemPrice,
                    'total' => ($itemPrice * $itemQuantity),
                ]);
            }

            // 6. Create Payment Record
            Payment::create([
                'booking_id' => $booking->id,
                'customer_id' => $request->customer_id,
                'barber_id' => $freeBarber->id,
                'amount' => $booking->total_price,
                'admin_commission' => $booking->admin_commission,
                'provider_earnings' => $booking->provider_earnings,
                'commission_rate' => $booking->commission_rate,
                'payment_type' => $request->payment_type,
                'booking_type' => $booking->booking_type,
                'payment_status' => 'pending',
            ]);

            // 7. Log Assignment and Notify
            BookingAssignHistory::create([
                'booking_id' => $booking->id,
                'barber_id' => $freeBarber->id,
                'status' => 'pending',
            ]);

            FcmService::sendNotification(
                $freeBarber->id,
                'New ASAP Booking Request',
                'You have a new ASAP booking request. Please accept within 3 minutes.',
                ['booking_id' => $booking->id]
            );

            DB::commit();

            // 8. Stripe Payment URL if online
            if ($request->payment_type == 'online') {
                Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

                $lineItems = [];
                foreach ($request->service_id as $index => $serviceId) {
                    $itemPrice = $request->price[$index] ?? 0;
                    $itemQuantity = $request->quantity[$index] ?? 1;
                    $unitAmount = intval(round((float) $itemPrice * 100));

                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Service ID: '.$serviceId,
                            ],
                            'unit_amount' => $unitAmount,
                        ],
                        'quantity' => $itemQuantity,
                    ];
                }

                $stripeSession = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => $frontendUrl.'/payment/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $frontendUrl.'/payment/cancel',
                    'client_reference_id' => $booking->id,
                ]);

                return $this->success([
                    'booking_id' => $booking->id,
                    'payment_url' => $stripeSession->url,
                ], 'ASAP Booking initiated. Please complete the payment.');
            }

            return $this->success($booking, 'ASAP Booking initiated. Searching for barber.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
}

