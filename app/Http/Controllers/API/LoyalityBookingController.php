<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItemManage;
use App\Models\BookingTimeMange;
use App\Models\LoyalityAdd;
use App\Models\LoyaltySetting;
use App\Models\SalonLoyality;
use App\Models\ScheduleTimeManage;
use App\Models\ServicePrice;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoyalityBookingController extends Controller
{
    use ApiResponse;

   public function loyaltyBooking(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date' => 'required|date',
        'customer_id' => 'required|exists:users,id',
        'barber_id' => 'required|exists:users,id',
        'salon_id' => 'nullable|exists:users,id', // ✅ FIXED
        'slot_id' => 'required|array',
        'slot_id.*' => 'required|exists:schedule_time_manages,id',
    ]);

    if ($validator->fails()) {
        return $this->error('Validation Error', $validator->errors());
    }

    DB::beginTransaction();

    try {
        $customerId = $request->customer_id;
        $barberId   = $request->barber_id;
        $salonId    = $request->salon_id;
        $requestedSlots = $request->slot_id;
        $date = Carbon::parse($request->date)->format('Y-m-d');

        // =========================
        // 1. Determine loyalty type
        // =========================
        if ($salonId) {
            // 🎯 SALON
            $setting = SalonLoyality::where('salon_id', $salonId)->first();

            if (!$setting) {
                return $this->error([], 'Salon loyalty not configured.', 422);
            }

            $requiredPoints   = $setting->service_reach_loyality;
            $loyaltyServiceId = $setting->service_id;
            $useType = 'salon';

        } else {
            // 🎯 HOME BARBER
            $setting = LoyaltySetting::first();

            if (!$setting) {
                return $this->error([], 'Global loyalty not configured.', 422);
            }

            $requiredPoints   = $setting->service_reach_loyality;
            $loyaltyServiceId = $setting->service_id ?? null; // optional
            $useType = 'barber';
        }

        // =========================
        // 2. Get total customer points
        // =========================
        $last = LoyalityAdd::where('customer_id', $customerId)
            ->latest('id')
            ->first();

        $currentPoints = $last ? $last->remaining_loyality_point : 0;

        if ($currentPoints < $requiredPoints) {
            return $this->error([], 'Not enough loyalty points.', 422);
        }

        // =========================
        // 3. Service duration check
        // =========================
        if ($loyaltyServiceId) {
            $servicePrice = ServicePrice::where('service_id', $loyaltyServiceId)
                ->when($salonId, fn($q) => $q->where('created_by', $salonId))
                ->first()
                ?? ServicePrice::where('service_id', $loyaltyServiceId)->first();

            if (!$servicePrice) {
                return $this->error([], 'Service duration not found.', 422);
            }

            $totalRequiredMinutes = $servicePrice->time_duration;
        } else {
            $totalRequiredMinutes = 0;
        }

        $slots = ScheduleTimeManage::whereIn('id', $requestedSlots)
            ->orderBy('scheduled_start_time')
            ->get();

        $totalSlotMinutes = 0;
        $previousEndTime = null;

        foreach ($slots as $slot) {
            $start = Carbon::parse($slot->scheduled_start_time);
            $end   = Carbon::parse($slot->scheduled_end_time);

            if ($previousEndTime && $start->ne($previousEndTime)) {
                throw new \Exception('Slots must be consecutive.');
            }

            $totalSlotMinutes += $start->diffInMinutes($end);
            $previousEndTime = $end;
        }

        if ($totalRequiredMinutes && $totalSlotMinutes < $totalRequiredMinutes) {
            throw new \Exception('Insufficient slot time.');
        }

        // =========================
        // 4. Availability
        // =========================
        if ($salonId) {
            $this->checkAvailability($salonId, $barberId, $date, $requestedSlots);
        }

        // =========================
        // 5. Create booking
        // =========================
        $booking = Booking::create([
            'customer_id' => $customerId,
            'barber_id'   => $barberId,
            'salon_id'    => $salonId,
            'subtotal'    => 0,
            'tax'         => 0,
            'total_price' => 0,
            'total_service_quantity' => 1,
            'payment_type' => 'loyalty',
            'booking_type' => 'loyalty',
            'payment_status' => 'paid',
            'status' => 'accepted',
            'booking_date' => $date,
        ]);

        // =========================
        // 6. Save slots
        // =========================
        foreach ($requestedSlots as $slotId) {
            $slotInfo = ScheduleTimeManage::find($slotId);

            BookingTimeMange::create([
                'booking_id' => $booking->id,
                'schedule_id' => $slotId,
                'barber_id' => $barberId,
                'salon_id' => $salonId,
                'date' => $date,
                'start_time' => $slotInfo?->scheduled_start_time,
                'end_time' => $slotInfo?->scheduled_end_time,
                'status' => 'active',
            ]);
        }

        // =========================
        // 7. Save service (optional)
        // =========================
        if ($loyaltyServiceId) {
            BookingItemManage::create([
                'booking_id' => $booking->id,
                'service_id' => $loyaltyServiceId,
                'quantity' => 1,
                'price' => 0,
                'total' => 0,
            ]);
        }

        // =========================
        // 8. Deduct loyalty
        // =========================
        $newRemaining = $currentPoints - $requiredPoints;

        LoyalityAdd::create([
            'customer_id' => $customerId,
            'salon_id' => $useType === 'salon' ? $salonId : null,
            'barber_id' => $useType === 'barber' ? $barberId : null,
            'booking_id' => $booking->id,
            'per_booking_loyality_point' => 0,
            'remaining_loyality_point' => $newRemaining,
            'costing_loyality_point' => $requiredPoints,
        ]);

        DB::commit();

        return $this->success([
            'booking_id' => $booking->id,
            'type' => $useType,
            'remaining_points' => $newRemaining
        ], 'Loyalty booking successful');

    } catch (\Exception $e) {
        DB::rollBack();
        return $this->error($e->getMessage());
    }
}
    private function checkAvailability($salonId, $barberId, $date, $requestedSlots)
    {
        $barber = User::where('id', $barberId)->where('salon_id', $salonId)->first();
        if (!$barber) {
            throw new \Exception('Invalid barber for this salon.');
        }

        $salonBlocked = SlotBlockBarber::where('salon_id', $salonId)
            ->whereDate('block_date', $date)
            ->whereNull('barber_id')
            ->whereIn('slot_id', $requestedSlots)
            ->where('status', 'active')
            ->exists();

        if ($salonBlocked) {
            throw new \Exception('Salon unavailable.');
        }

        $barberBlocked = SlotBlockBarber::where('salon_id', $salonId)
            ->where('barber_id', $barberId)
            ->whereDate('block_date', $date)
            ->whereIn('slot_id', $requestedSlots)
            ->where('status', 'active')
            ->exists();

        if ($barberBlocked) {
            throw new \Exception('Barber blocked.');
        }

        $alreadyBooked = BookingTimeMange::where('barber_id', $barberId)
            ->whereDate('date', $date)
            ->whereIn('schedule_id', $requestedSlots)
            ->where('status', 'active')
            ->exists();

        if ($alreadyBooked) {
            throw new \Exception('Selected barber busy.');
        }
    }
}
