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
use App\Services\AsapBookingNotificationPayloadService;
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
            'start_time' => 'required', // Format: HH:mm (Example: 10:00)
            'end_time' => 'nullable',   // Optional: calculated if not provided
            'service_id' => 'required|array',
            'service_id.*' => 'required|exists:services,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:1',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'subtotal' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->error([],'Validation Error', $validator->errors());
        }

        if (count($request->service_id) != count($request->price) || count($request->service_id) != count($request->quantity)) {
            return $this->error([],'Service, quantity, and price count mismatch');
        }

        $customer = User::find($request->customer_id);
        if (!$customer->latitude || !$customer->longitude) {
            return $this->error([],'Customer location not found. Please update your profile.');
        }

        DB::beginTransaction();

        try {
            $date = Carbon::parse($request->date)->format('Y-m-d');
            $startTimeString = $request->start_time;

            // 1. Calculate Total Required Minutes from Services
            $totalRequiredMinutes = 0;
            foreach ($request->service_id as $index => $serviceId) {
                $servicePrice = ServicePrice::where('service_id', $serviceId)->first();
                if (!$servicePrice) {
                    throw new \Exception("Service price not found for service ID: {$serviceId}");
                }

                $qty = $request->quantity[$index];
                $totalRequiredMinutes += ($servicePrice->time_duration * $qty);
            }

            // 2. Determine requested start time and minimum end time from service duration
            $start = Carbon::parse($startTimeString);
            $startTime = $start->format('H:i:s');

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
                // ->whereNotNull('latitude') // Temporarily lenient for testing
                // ->whereNotNull('longitude')
                ->havingRaw("distance <= ? OR distance IS NULL", [$radius]);

            $barberSlots = collect();
            $freeBarber = $query
                ->orderBy('distance', 'asc')
                ->get()
                ->first(function ($barber) use ($date, $startTime, $totalRequiredMinutes, &$barberSlots) {
                    $barberSlots = $this->findConsecutiveAvailableSlots(
                        $barber->id,
                        $date,
                        $startTime,
                        $totalRequiredMinutes
                    );

                    return $barberSlots->isNotEmpty();
                });

            if (!$freeBarber) {
                $reason = "No home_barbar found within {$radius}km with enough free consecutive schedule slots from or after {$startTime}.";
               return $this->error([], $reason);
            }

            $startTime = Carbon::parse($barberSlots->first()->scheduled_start_time)->format('H:i:s');
            $endTime = Carbon::parse($barberSlots->last()->scheduled_end_time)->format('H:i:s');

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

            // 5. Save the consecutive schedule slots that cover the estimated service time
            foreach ($barberSlots as $slot) {
                BookingTimeMange::create([
                    'booking_id' => $booking->id,
                    'barber_id' => $freeBarber->id,
                    'schedule_id' => $slot->id,
                    'date' => $date,
                    'status' => 'active',
                    'start_time' => $slot->scheduled_start_time,
                    'end_time' => $slot->scheduled_end_time,
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
                'salon_id' => $freeBarber->salon_id,
                'booking_type' => 'as_soon_possible',
                'status' => 'pending',
            ]);

            $booking->load(['items.service']);
            $customerAddress = $request->input('address') ?? $request->input('customer_address');
            $notificationData = AsapBookingNotificationPayloadService::build(
                $booking,
                $customer,
                $freeBarber,
                $barberSlots,
                $totalRequiredMinutes,
                $customerAddress
            );

            FcmService::sendNotification(
                $freeBarber->id,
                'New ASAP Booking Request',
                'You have a new ASAP booking request. Please accept within 3 minutes.',
                $notificationData
            );

            DB::commit();

            $booking->load(['items.service', 'slots', 'barber']);

            return $this->success($booking, 'ASAP Booking initiated. Searching for barber. Payment will be required after a barber accepts.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    private function findConsecutiveAvailableSlots(int $barberId, string $date, string $startTime, int $requiredMinutes)
    {
        $blockedSlotIds = SlotBlockBarber::where('barber_id', $barberId)
            ->whereDate('block_date', $date)
            ->where('status', 'active')
            ->pluck('slot_id')
            ->toArray();

        $bookedSlotIds = BookingTimeMange::where('barber_id', $barberId)
            ->whereDate('date', $date)
            ->whereIn('status', ['active', 'pending'])
            ->whereNotNull('schedule_id')
            ->pluck('schedule_id')
            ->toArray();

        $unavailableSlotIds = array_unique(array_merge($blockedSlotIds, $bookedSlotIds));

        $slots = ScheduleTimeManage::where('provider_id', $barberId)
            ->where('status', 'active')
            ->where('scheduled_start_time', '>=', $startTime)
            ->whereNotIn('id', $unavailableSlotIds)
            ->orderBy('scheduled_start_time')
            ->get();

        for ($index = 0; $index < $slots->count(); $index++) {
            $selectedSlots = collect();
            $coveredMinutes = 0;
            $expectedStartTime = Carbon::parse($slots[$index]->scheduled_start_time)->format('H:i:s');

            for ($slotIndex = $index; $slotIndex < $slots->count(); $slotIndex++) {
                $slot = $slots[$slotIndex];
                $slotStart = Carbon::parse($slot->scheduled_start_time)->format('H:i:s');
                $slotEnd = Carbon::parse($slot->scheduled_end_time)->format('H:i:s');

                if ($slotStart !== $expectedStartTime) {
                    break;
                }

                $selectedSlots->push($slot);
                $coveredMinutes += Carbon::parse($slotStart)->diffInMinutes(Carbon::parse($slotEnd));

                if ($coveredMinutes >= $requiredMinutes) {
                    return $selectedSlots;
                }

                $expectedStartTime = $slotEnd;
            }
        }

        return collect();
    }
}
