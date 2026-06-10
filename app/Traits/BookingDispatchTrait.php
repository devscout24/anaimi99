<?php

namespace App\Traits;

use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Models\BookingTimeMange;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Services\AsapBookingNotificationPayloadService;
use App\Services\FcmService;

trait BookingDispatchTrait
{
    /**
     * Find the next nearest available home barber for an ASAP booking.
     */
    public function findNextAvailableBarber(Booking $booking)
    {
        $customer = $booking->customer;
        if (!$customer || !$customer->latitude || !$customer->longitude) {
            return null;
        }

        $userLat = (float) $customer->latitude;
        $userLng = (float) $customer->longitude;
        $radius = 50;
        $date = $booking->booking_date;

        // Get requested time range from existing booking slots
        $firstSlot = BookingTimeMange::where('booking_id', $booking->id)
            ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
            ->orderBy('schedule_time_manages.scheduled_start_time', 'asc')
            ->first();

        $lastSlot = BookingTimeMange::where('booking_id', $booking->id)
            ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
            ->orderBy('schedule_time_manages.scheduled_end_time', 'desc')
            ->first();

        if (!$firstSlot || !$lastSlot) {
            return null;
        }

        $startTime = $firstSlot->scheduled_start_time;
        $endTime = $lastSlot->scheduled_end_time;

        // Get barbers already tried for this booking
        $triedBarberIds = BookingAssignHistory::where('booking_id', $booking->id)->pluck('barber_id')->toArray();

        $distanceSql = '( 6371 * ACOS( COS( RADIANS(?) ) * COS( RADIANS(latitude) ) *
                          COS( RADIANS(longitude) - RADIANS(?) ) +
                          SIN( RADIANS(?) ) * SIN( RADIANS(latitude) ) ) )';

        $query = User::selectRaw("users.*, {$distanceSql} AS distance", [$userLat, $userLng, $userLat])
            ->where('role', 'home_barbar')
            ->where('status', 'approved')
            ->where('block_status', 'unblock')
            ->where('availability', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotIn('id', $triedBarberIds)
            ->havingRaw("{$distanceSql} <= ?", [$userLat, $userLng, $userLat, $radius]);

        // Availability checks:
        // 1. Barber must HAVE slots in this range
        $query->whereHas('schedule_time_manages', function ($q) use ($startTime, $endTime) {
            $q->where('scheduled_start_time', '>=', $startTime)
                ->where('scheduled_end_time', '<=', $endTime)
                ->where('status', 'active');
        });

        // 2. Barber must NOT be booked/blocked for any slot overlapping this range
        $unavailableBarberIds = \DB::table('booking_time_manges')
            ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
            ->where('booking_time_manges.date', $date)
            ->where('booking_time_manges.status', 'active')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('schedule_time_manages.scheduled_start_time', [$startTime, $endTime])
                    ->orWhereBetween('schedule_time_manages.scheduled_end_time', [$startTime, $endTime]);
            })
            ->pluck('barber_id')
            ->toArray();

        $blockedBarberIds = \DB::table('slot_block_barbers')
            ->join('schedule_time_manages', 'slot_block_barbers.slot_id', '=', 'schedule_time_manages.id')
            ->where('slot_block_barbers.block_date', $date)
            ->where('slot_block_barbers.status', 'active')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('schedule_time_manages.scheduled_start_time', [$startTime, $endTime])
                    ->orWhereBetween('schedule_time_manages.scheduled_end_time', [$startTime, $endTime]);
            })
            ->pluck('barber_id')
            ->toArray();

        $unavailable = array_unique(array_merge($unavailableBarberIds, $blockedBarberIds));

        if (!empty($unavailable)) {
            $query->whereNotIn('id', $unavailable);
        }

        return $query->orderBy('distance', 'asc')->first();
    }

    /**
     * Reassign an ASAP booking to the next available barber.
     */
    public function reassignAsapBooking(Booking $booking)
    {
        // Mark current assignment as timeout
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $booking->barber_id)
            ->where('status', 'pending')
            ->update(['status' => 'timeout']);

        $nextBarber = $this->findNextAvailableBarber($booking);

        if ($nextBarber) {
            // Get times from old slots to find new slots
            $firstSlot = BookingTimeMange::where('booking_id', $booking->id)
                ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
                ->orderBy('schedule_time_manages.scheduled_start_time', 'asc')
                ->first();
            $lastSlot = BookingTimeMange::where('booking_id', $booking->id)
                ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
                ->orderBy('schedule_time_manages.scheduled_end_time', 'desc')
                ->first();

            $startTime = $firstSlot->scheduled_start_time;
            $endTime = $lastSlot->scheduled_end_time;

            // Delete old slots for this booking (they were tied to old barber)
            BookingTimeMange::where('booking_id', $booking->id)->delete();

            // Find new slots for the next barber
            $newSlots = \App\Models\ScheduleTimeManage::where('provider_id', $nextBarber->id)
                ->where('scheduled_start_time', '>=', $startTime)
                ->where('scheduled_end_time', '<=', $endTime)
                ->where('status', 'active')
                ->get();

            foreach ($newSlots as $slot) {
                BookingTimeMange::create([
                    'booking_id' => $booking->id,
                    'barber_id' => $nextBarber->id,
                    'schedule_id' => $slot->id,
                    'date' => $booking->booking_date,
                    'status' => 'active',
                    'start_time' => $slot->scheduled_start_time,
                    'end_time' => $slot->scheduled_end_time,
                ]);
            }

            $booking->barber_id = $nextBarber->id;
            $booking->last_assigned_at = now();
            $booking->save();

            // Log new assignment
            BookingAssignHistory::create([
                'booking_id' => $booking->id,
                'barber_id' => $nextBarber->id,
                'salon_id' => $nextBarber->salon_id,
                'booking_type' => $booking->booking_type,
                'status' => 'pending',
            ]);

            // Notify new barber
            $booking->load(['items.service', 'slots', 'customer']);

            // Calculate total duration from items
            $totalRequiredMinutes = 0;
            foreach ($booking->items as $item) {
                $srvPrice = \App\Models\ServicePrice::where('service_id', $item->service_id)->first();
                $duration = $srvPrice ? $srvPrice->time_duration : 0;
                $totalRequiredMinutes += ($duration * $item->quantity);
            }

            $notificationData = AsapBookingNotificationPayloadService::build(
                $booking,
                $booking->customer,
                $nextBarber,
                $newSlots,
                $totalRequiredMinutes
            );

            FcmService::sendNotification(
                $nextBarber->id,
                'New ASAP Booking Request',
                'A new ASAP booking request is available. Please accept within 3 minutes.',
                $notificationData
            );

            return $nextBarber;
        } else {
            $booking->status = 'cancelled';
            $booking->save();
            return null;
        }
    }
}
