<?php

namespace App\Traits;

use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Models\BookingTimeMange;
use App\Models\SlotBlockBarber;
use App\Models\User;
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

        // Get requested slots
        $requestedSlots = BookingTimeMange::where('booking_id', $booking->id)->pluck('schedule_id')->toArray();

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

        // Availability checks
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

        return $query->orderBy('distance', 'asc')->first();
    }

    /**
     * Reassign an ASAP booking to the next available barber.
     */
    public function reassignAsapBooking(Booking $booking)
    {
        // Mark current assignment as timed_out
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $booking->barber_id)
            ->where('status', 'pending')
            ->update(['status' => 'timed_out']);

        $nextBarber = $this->findNextAvailableBarber($booking);

        if ($nextBarber) {
            $booking->barber_id = $nextBarber->id;
            $booking->last_assigned_at = now();
            $booking->save();

            // Log new assignment
            BookingAssignHistory::create([
                'booking_id' => $booking->id,
                'barber_id' => $nextBarber->id,
                'status' => 'pending',
            ]);

            // Notify new barber
            FcmService::sendNotification(
                $nextBarber->id,
                'New ASAP Booking Request',
                'A new ASAP booking request is available. Please accept within 3 minutes.',
                ['booking_id' => $booking->id]
            );

            return $nextBarber;
        } else {
            $booking->status = 'cancelled';
            $booking->save();
            return null;
        }
    }
}
