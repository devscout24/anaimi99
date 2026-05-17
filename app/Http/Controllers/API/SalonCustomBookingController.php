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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SalonCustomBookingController extends Controller
{
    use ApiResponse;

    public function salonCustomBooking(Request $request)
    {



       
        $validator = Validator::make($request->all(), [

            'date' => 'required|date',

            'customer_id' => 'nullable|exists:users,id',
            'name' => 'required_without:customer_id|string|max:255',
            'email' => 'required_without:customer_id|email|unique:users,email',
            'phone' => 'required_without:customer_id|unique:users,phone',

            'barber_id' => 'required|exists:users,id',
            'salon_id' => 'required|exists:users,id',

            'payment_type' => 'required|in:onsite,cod',

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

            $customerId = $request->customer_id;

            if (!$customerId) {
                $randomPassword = Str::random(8);

                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->password = Hash::make($randomPassword);
                $user->save();

                $user->assignRole('customer');

                $customerId = $user->id;

                try {
                    Mail::raw("Welcome to our booking system!\n\nYour account has been created.\nEmail: {$user->email}\nPassword: {$randomPassword}\n\nPlease keep this secure and change your password when you log in.", function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('Your New Account Credentials');
                    });
                } catch (\Exception $mailException) {
                    \Illuminate\Support\Facades\Log::error("Failed to send welcome email to {$user->email}: " . $mailException->getMessage());
                }
            }


            $assignedBarberId = $request->barber_id;
            $salonId = $request->salon_id;

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

                $servicePrice = ServicePrice::where('service_id', $serviceId)
                    ->where('created_by', $salonId)
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

            $previousEndTime = null;

            foreach ($slots as $slot) {
                $start = Carbon::parse($slot->scheduled_start_time);
                $end = Carbon::parse($slot->scheduled_end_time);

                // Consecutiveness check
                if ($previousEndTime && $start->ne($previousEndTime)) {
                    throw new \Exception('Selected slots must be strictly consecutive.');
                }

                $totalSlotMinutes += $start->diffInMinutes($end);
                $previousEndTime = $end;
            }

            // 3. Compare Durations
            if ($totalSlotMinutes < $totalRequiredMinutes) {
                throw new \Exception("Selected slots cover {$totalSlotMinutes} minutes, but services require {$totalRequiredMinutes} minutes.");
            }

            /*
            =====================================
            CHECK AVAILABILITY
            =====================================
            */

            /*
            selected barber belongs to salon?
            */

            $barber =
            User::where(
                'id',
                $assignedBarberId
            )
                ->where(
                    'salon_id',
                    $salonId
                )
                ->lockForUpdate()
                ->first();

            if (! $barber) {
                throw new \Exception(
                    'Invalid barber for this salon.'
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
                    $assignedBarberId
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
                $assignedBarberId
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

            /*
            =====================================
            BOOKING CREATE
            =====================================
            */

            $booking =
            new Booking;

            $booking->customer_id =
            $customerId;

            $booking->barber_id =
            $assignedBarberId;

            $booking->salon_id =
            $salonId;

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
            'custom';

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
                'custom'
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

                    'salon_id' => $salonId,

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
                'customer_id' => $customerId,
                'barber_id' => $assignedBarberId,
                'salon_id' => $salonId,
                'amount' => $booking->total_price,
                'admin_commission' => $booking->admin_commission,
                'provider_earnings' => $booking->provider_earnings,
                'commission_rate' => $booking->commission_rate,
                'payment_type' => 'custom',
                'booking_type' => 'custom',
                'payment_status' => 'pending',
            ]);

            DB::commit();

            return $this->success(
                [
                    'assigned_barber_id' => $assignedBarberId,
                    'customer_id' => $customerId,
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

