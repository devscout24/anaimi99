<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItemManage;
use App\Models\BookingTimeMange;
use App\Models\CommissionSetting;
use App\Models\Payment;
use App\Models\ScheduleTimeManage;
use App\Models\ServicePrice;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CustomerBookingController extends Controller
{
    use ApiResponse;

    public function bookingslotscustomer(Request $request)
    {

     

        $validator = Validator::make($request->all(), [

            'date' => 'required|date',

            'customer_id' => 'required|exists:users,id',

            'barber_id' => [
                'nullable',
                'exists:users,id',
                'required_if:request_type,salon_barber',
            ],

            'salon_id' => 'nullable|exists:users,id',

            'payment_type' => 'required|in:online,onsite,cod',

            'request_type' => 'required|in:salon_auto,salon_barber,home_barber,as_soon_possible',

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
            return $this->error(
                'Validation Error',
                $validator->errors()
            );
        }

        /*
        service, quantity & price count validation
        */
        if (
            count($request->service_id) != count($request->price) ||
            count($request->service_id) != count($request->quantity)
        ) {
            return $this->error(
                'Service, quantity, and price count mismatch'
            );
        }

        DB::beginTransaction();

        try {

            $assignedBarberId = $request->barber_id;

            $requestedSlots = $request->slot_id;

            $date = Carbon::parse(
                $request->date
            )->format('Y-m-d');

            /*
            =====================================
            SLOT DURATION & CONSECUTIVENESS CHECK
            =====================================
            */
            // 1. Calculate Total Required Minutes
            $totalRequiredMinutes = 0;
            foreach ($request->service_id as $index => $serviceId) {
                // Determine owner for ServicePrice
                $priceOwnerId = in_array($request->request_type, ['salon_auto', 'salon_barber']) ? $request->salon_id : $request->barber_id;

                $servicePrice = ServicePrice::where('service_id', $serviceId)
                    ->where('created_by', $priceOwnerId)
                    ->first();

                // Fallback for testing: if the salon hasn't set a price, just take any available price for this service
                if (! $servicePrice) {
                    $servicePrice = ServicePrice::where('service_id', $serviceId)->first();
                }

                if (! $servicePrice) {
                    throw new \Exception("Service price not found for service ID: {$serviceId}");
                }

                $qty = $request->quantity[$index];
                $totalRequiredMinutes += ($servicePrice->time_duration * $qty);
            }

            // 2. Calculate Total Slot Minutes & Check Consecutiveness
            $totalSlotMinutes = 0;

            // Get slots ordered by time
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

            // 3. Compare Durations
            if ($totalSlotMinutes < $totalRequiredMinutes) {
                throw new \Exception("Selected slots cover {$totalSlotMinutes} minutes, but services require {$totalRequiredMinutes} minutes.");
            }

            /*
            =====================================
            SALON AUTO
            =====================================
            */

            if (
                $request->request_type
                ==
                'salon_auto'
            ) {

                $salonId = $request->salon_id;

                if (! $salonId) {
                    throw new \Exception(
                        'Salon ID required.'
                    );
                }

                $salonBlocked =
                SlotBlockBarber::where(
                    'salon_id',
                    $salonId
                )
                    ->whereDate(
                        'block_date',
                        $date
                    )
                    ->whereNull(
                        'barber_id'
                    )
                    ->whereIn(
                        'slot_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($salonBlocked) {
                    throw new \Exception(
                        'Salon unavailable.'
                    );
                }

                $blockedBarbers =
                SlotBlockBarber::where(
                    'salon_id',
                    $salonId
                )
                    ->whereNotNull(
                        'barber_id'
                    )
                    ->whereDate(
                        'block_date',
                        $date
                    )
                    ->whereIn(
                        'slot_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->pluck(
                        'barber_id'
                    )
                    ->toArray();

                $bookedBarbers =
                BookingTimeMange::where(
                    'salon_id',
                    $salonId
                )
                    ->whereDate(
                        'date',
                        $date
                    )
                    ->whereNotNull(
                        'barber_id'
                    )
                    ->whereIn(
                        'schedule_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->pluck(
                        'barber_id'
                    )
                    ->toArray();

                $unavailable =
                array_unique(
                    array_merge(
                        $blockedBarbers,
                        $bookedBarbers
                    )
                );

                $freeBarber =
                User::where(
                    'salon_id',
                    $salonId
                )
                    ->whereNotIn(
                        'id',
                        $unavailable
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $freeBarber) {
                    throw new \Exception(
                        'No barber available.'
                    );
                }

                $assignedBarberId =
                $freeBarber->id;

            }

            /*
            =====================================
            SALON SPECIFIC BARBER
            =====================================
            */

            if (
                $request->request_type
                ==
                'salon_barber'
            ) {

                $salonId =
                $request->salon_id;

                $barberId =
                $request->barber_id;

                if (
                    ! $salonId
                    ||
                    ! $barberId
                ) {
                    throw new \Exception(
                        'Salon and barber required.'
                    );
                }

                /*
                selected barber belongs salon?
                */

                $barber =
                User::where(
                    'id',
                    $barberId
                )
                    ->where(
                        'salon_id',
                        $salonId
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $barber) {
                    throw new \Exception(
                        'Invalid barber.'
                    );
                }

                /*
                salon blocked?
                */

                $salonBlocked =
                SlotBlockBarber::where(
                    'salon_id',
                    $salonId
                )
                    ->whereDate(
                        'block_date',
                        $date
                    )
                    ->whereNull(
                        'barber_id'
                    )
                    ->whereIn(
                        'slot_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($salonBlocked) {
                    throw new \Exception(
                        'Salon unavailable.'
                    );
                }

                /*
                barber blocked?
                */

                $barberBlocked =
                SlotBlockBarber::where(
                    'salon_id',
                    $salonId
                )
                    ->where(
                        'barber_id',
                        $barberId
                    )
                    ->whereDate(
                        'block_date',
                        $date
                    )
                    ->whereIn(
                        'slot_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($barberBlocked) {
                    throw new \Exception(
                        'Barber blocked.'
                    );
                }

                /*
                already booked?
                */

                $alreadyBooked =
                BookingTimeMange::where(
                    'barber_id',
                    $barberId
                )
                    ->whereDate(
                        'date',
                        $date
                    )
                    ->whereIn(
                        'schedule_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($alreadyBooked) {
                    throw new \Exception(
                        'Selected barber busy.'
                    );
                }

                $assignedBarberId =
                $barberId;

            }

            /*
            =====================================
            HOME BARBER
            =====================================
            */

            if (
                $request->request_type
                ==
                'home_barber'
            ) {

                $barberId =
                $request->barber_id;

                if (
                    ! $barberId
                ) {
                    throw new \Exception(
                        'Barber ID required.'
                    );
                }

                /*
                barber blocked?
                */

                $barberBlocked =
                SlotBlockBarber::where(
                    'barber_id',
                    $barberId
                )
                    ->whereDate(
                        'block_date',
                        $date
                    )
                    ->whereIn(
                        'slot_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($barberBlocked) {
                    throw new \Exception(
                        'Barber blocked.'
                    );
                }

                /*
                already booked?
                */

                $alreadyBooked =
                BookingTimeMange::where(
                    'barber_id',
                    $barberId
                )
                    ->whereDate(
                        'date',
                        $date
                    )
                    ->whereIn(
                        'schedule_id',
                        $requestedSlots
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($alreadyBooked) {
                    throw new \Exception(
                        'Selected barber busy.'
                    );
                }

                $assignedBarberId =
                $barberId;

            }

            /*
            =====================================
            BOOKING CREATE
            =====================================
            */

            if (
                $assignedBarberId
                &&
                in_array(
                    $request->payment_type,
                    ['cod', 'online']
                )
            ) {

                $booking =
                new Booking;

                $booking->customer_id =
                $request->customer_id;

                $booking->barber_id =
                $assignedBarberId;

                $booking->salon_id =
                $request->salon_id;

                $booking->subtotal =
                $request->subtotal
                ??
                array_sum(
                    $request->price
                );

                $booking->tax =
                $request->tax ?? 0;

                $booking->total_price =
                $request->total_price
                ??
                array_sum(
                    $request->price
                );

                $booking->total_service_quantity =
                count(
                    $request->service_id
                );

                $booking->payment_type =
                $request->payment_type;

                $booking->booking_type =
                $request->request_type;

                $booking->payment_status =
                'pending';

                $booking->status =
                'accepted';

                $booking->booking_date =
                $date;

                /*
                commission calculation
                */
                $commission = CommissionSetting::calculateCommission(
                    (float) $booking->total_price,
                    $booking->booking_type
                );

                $booking->commission_rate = $commission['commission_rate'];
                $booking->admin_commission = $commission['admin_commission'];
                $booking->provider_earnings = $commission['provider_earnings'];

                $booking->save();

                /*
                save booking slots
                */

                foreach (
                    $request->slot_id as $slotId
                ) {

                    $slotInfo =
                    ScheduleTimeManage::find(
                        $slotId
                    );

                    BookingTimeMange::create([

                        'booking_id' => $booking->id,

                        'schedule_id' => $slotId,

                        'barber_id' => $assignedBarberId,

                        'salon_id' => $request->salon_id,

                        'date' => $date,

                        'start_time' => $slotInfo
                        ?
                        $slotInfo->scheduled_start_time
                        : null,

                        'end_time' => $slotInfo
                        ?
                        $slotInfo->scheduled_end_time
                        : null,

                        'status' => 'active',

                    ]);

                }

                /*
                save services
                */

                foreach (
                    $request->service_id as $index => $serviceId
                ) {

                    $itemPrice =
                    $request->price[$index]
                    ?? 0;

                    $itemQuantity =
                    $request->quantity[$index]
                    ?? 1;

                    BookingItemManage::create([

                        'booking_id' => $booking->id,

                        'service_id' => $serviceId,

                        'quantity' => $itemQuantity,

                        'price' => $itemPrice,

                        'total' => ($itemPrice * $itemQuantity),

                    ]);

                }

                /*
                create payment record
                */
                Payment::create([
                    'booking_id' => $booking->id,
                    'customer_id' => $request->customer_id,
                    'barber_id' => $assignedBarberId,
                    'salon_id' => $request->salon_id,
                    'amount' => $booking->total_price,
                    'admin_commission' => $booking->admin_commission,
                    'provider_earnings' => $booking->provider_earnings,
                    'commission_rate' => $booking->commission_rate,
                    'payment_type' => $request->payment_type,
                    'booking_type' => $booking->booking_type,
                    'payment_status' => 'pending',
                ]);

            }

            DB::commit();

            if ($request->payment_type == 'online') {
                Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

                // Fetch service details for Stripe line items
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

                $paymentUrl = $stripeSession->url;

                return $this->success(
                    [
                        'assigned_barber_id' => $assignedBarberId,
                        'booking_id' => $booking->id ?? null,
                        'payment_url' => $paymentUrl,
                    ],
                    'Booking created successfully. Please complete the payment.'
                );
            }

            return $this->success(
                [
                    'assigned_barber_id' => $assignedBarberId,

                    'booking_id' => $booking->id ?? null,
                ],
                'Booking created successfully'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->error(
                $e->getMessage()
            );

        }

    }
}

