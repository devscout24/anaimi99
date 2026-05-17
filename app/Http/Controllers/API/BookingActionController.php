<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Traits\ApiResponse;
use App\Traits\BookingDispatchTrait;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingActionController extends Controller
{
    use ApiResponse, BookingDispatchTrait;

    /**
     * Barber accepts the ASAP booking.
     */
    public function acceptAsapBooking(Request $request, $id)
    {
        $barber = Auth::guard('api')->user();

        $booking = Booking::where('id', $id)
            ->where('barber_id', $barber->id)
            ->where('status', 'search_barber')
            ->first();

        if (!$booking) {
            return $this->error('Booking not found or already processed.');
        }

        // Update booking status
        $booking->status = 'accepted';
        $booking->save();

        // Update history
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $barber->id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);

        // Notify customer
        FcmService::sendNotification(
            $booking->customer_id,
            'Booking Accepted',
            'Your ASAP booking has been accepted by ' . $barber->name,
            ['booking_id' => $booking->id]
        );

        return $this->success($booking, 'Booking accepted successfully.');
    }

    /**
     * Barber rejects the ASAP booking.
     */
    public function rejectAsapBooking(Request $request, $id)
    {
        $barber = Auth::guard('api')->user();

        $booking = Booking::where('id', $id)
            ->where('barber_id', $barber->id)
            ->where('status', 'search_barber')
            ->first();

        if (!$booking) {
            return $this->error('Booking not found or already processed.');
        }

        // Update history to rejected
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $barber->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        // Immediately try to find the next barber
        $nextBarber = $this->reassignAsapBooking($booking);

        if ($nextBarber) {
            return $this->success(['next_barber_id' => $nextBarber->id], 'Booking rejected. Assigned to next available barber.');
        } else {
            return $this->success([], 'Booking rejected. No more barbers available.');
        }
    }
}

