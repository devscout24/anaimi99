<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingTimeMange;
use App\Models\ScheduleDay;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BarberschedulebookingController extends Controller
{
    use ApiResponse;

    public function getSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_type' => 'required|in:home_barber,salon,salon_barbar',
            'barber_id' => 'required_if:provider_type,home_barber,salon_barbar|nullable|exists:users,id',
            'salon_id' => 'required_if:provider_type,salon,salon_barbar|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        try {
            $providerType = $request->provider_type;
            $barberId = $request->barber_id;
            $salonId = $request->salon_id;
            $date = Carbon::parse($request->date)->format('Y-m-d');

            // Find the schedule owner (who owns the ScheduleDay)
            $ownerId = in_array($providerType, ['salon', 'salon_barbar']) ? $salonId : $barberId;

            // Get Schedule
            $schedule = ScheduleDay::with('scheduleTimeManages')
                ->where('provider_id', $ownerId)
                ->latest()
                ->first();

            if (!$schedule) {
                return $this->notFound([], 'No schedule found for this provider.');
            }

            $availableSlots = [];

            if ($providerType === 'salon' && empty($barberId)) {
                // Salon only (no specific barber selected)
                // Get total barbers in this salon
                $salonBarbers = User::where('salon_id', $salonId)->pluck('id')->toArray();
                $totalBarbers = count($salonBarbers);

                // Salon-level completely blocked slots (no barber_id specified)
                $salonBlockedSlots = SlotBlockBarber::where('status', 'active')
                    ->whereDate('block_date', $date)
                    ->where('salon_id', $salonId)
                    ->whereNull('barber_id')
                    ->pluck('slot_id')
                    ->toArray();

                // Salon-level booked slots (no barber_id specified)
                $salonBookedSlots = BookingTimeMange::where('status', 'active')
                    ->whereDate('date', $date)
                    ->where('salon_id', $salonId)
                    ->whereNull('barber_id')
                    ->pluck('schedule_id')
                    ->toArray();

                // For barber-level unavailability, group by slot_id and count distinct barbers
                $barberBlocked = SlotBlockBarber::where('status', 'active')
                    ->whereDate('block_date', $date)
                    ->where('salon_id', $salonId)
                    ->whereNotNull('barber_id')
                    ->get(['slot_id', 'barber_id']);

                $barberBooked = BookingTimeMange::where('status', 'active')
                    ->whereDate('date', $date)
                    ->where('salon_id', $salonId)
                    ->whereNotNull('barber_id')
                    ->get(['schedule_id as slot_id', 'barber_id']);

                $slotBarberUnavailability = [];

                foreach ($barberBlocked as $bb) {
                    $slotBarberUnavailability[$bb->slot_id][] = $bb->barber_id;
                }
                foreach ($barberBooked as $bk) {
                    $slotBarberUnavailability[$bk->slot_id][] = $bk->barber_id;
                }

                foreach ($schedule->scheduleTimeManages as $slot) {
                    // Hide if salon-level block exists
                    if (in_array($slot->id, $salonBlockedSlots)) {
                        continue;
                    }

                    $isBooked = false;

                    if (in_array($slot->id, $salonBookedSlots)) {
                        $isBooked = true;
                    } elseif ($totalBarbers > 0) {
                        // Check if all barbers are unavailable for this slot
                        $unavailableBarbers = isset($slotBarberUnavailability[$slot->id]) 
                                            ? count(array_unique($slotBarberUnavailability[$slot->id])) 
                                            : 0;
                        if ($unavailableBarbers >= $totalBarbers) {
                            $isBooked = true;
                        }
                    }

                    $availableSlots[] = [
                        'slot_id' => $slot->id,
                        'start_time' => $slot->scheduled_start_time,
                        'end_time' => $slot->scheduled_end_time,
                        'is_booked' => $isBooked,
                    ];
                }

            } else {
                // Specific barber selected (home_barber OR salon -> barber)
                $blockedSlotsQuery = SlotBlockBarber::where('status', 'active')
                    ->whereDate('block_date', $date);

                if (in_array($providerType, ['salon', 'salon_barbar'])) {
                    $blockedSlotsQuery->where(function($q) use ($salonId, $barberId) {
                        $q->where('salon_id', $salonId)->whereNull('barber_id') // Salon level block
                          ->orWhere('barber_id', $barberId); // Barber level block
                    });
                } else {
                    $blockedSlotsQuery->where('barber_id', $barberId);
                }

                $blockedSlotIds = $blockedSlotsQuery->pluck('slot_id')->toArray();

                $bookedSlotsQuery = BookingTimeMange::where('status', 'active')
                    ->whereDate('date', $date);

                if (in_array($providerType, ['salon', 'salon_barbar'])) {
                    $bookedSlotsQuery->where('salon_id', $salonId)->where('barber_id', $barberId);
                } else {
                    $bookedSlotsQuery->where('barber_id', $barberId);
                }

                $bookedSlotIds = $bookedSlotsQuery->pluck('schedule_id')->toArray();

                foreach ($schedule->scheduleTimeManages as $slot) {
                    // Hide if completely blocked
                    if (in_array($slot->id, $blockedSlotIds)) {
                        continue;
                    }

                    // Mark as booked if booked
                    $isBooked = in_array($slot->id, $bookedSlotIds);

                    $availableSlots[] = [
                        'slot_id' => $slot->id,
                        'start_time' => $slot->scheduled_start_time,
                        'end_time' => $slot->scheduled_end_time,
                        'is_booked' => $isBooked,
                    ];
                }
            }

            return $this->success([
                'provider_type' => $providerType,
                'barber_id' => $barberId,
                'salon_id' => $salonId,
                'date' => $date,
                'slots' => $availableSlots
            ]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


    
}

