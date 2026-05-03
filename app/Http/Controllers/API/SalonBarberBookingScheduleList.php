<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalonBarberBookingScheduleList extends Controller
{
    use ApiResponse;
    public function salonBarberBookingScheduleList(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();


            if (!$user || $user->role !== 'salon' || $user->status !== "approved") {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only approved salons can access this dashboard.'
                ], 403);
            }


            $date = $request->date ?? now()->toDateString();


            $barbers =User::query()->where('salon_id', $user->id)
                ->where('role', 'salon_barbar')
                ->select('id', 'name', 'profile_image')
                ->get()
                ->map(function ($barber) {
                    $barber->profile_image = $barber->profile_image ? asset($barber->profile_image) : null;
                    return $barber;
                });


            $allBookings = Booking::query()->where('salon_id', $user->id)
                ->where('booking_date', $date)
                ->with(['customer:id,name,profile_image', 'barber:id,name', 'items.service', 'slots.scheduleTime'])
                ->get()
                ->map(function ($booking) {
                    // Extract start and end time from slots
                    $startTimeSlot = $booking->slots->sortBy(function($slot) {
                        return optional($slot->scheduleTime)->scheduled_start_time;
                    })->first();

                    $endTimeSlot = $booking->slots->sortByDesc(function($slot) {
                        return optional($slot->scheduleTime)->scheduled_end_time;
                    })->first();

                    $startTime = $startTimeSlot && $startTimeSlot->scheduleTime
                        ? \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('H:i')
                        : null;

                    $endTime = $endTimeSlot && $endTimeSlot->scheduleTime
                        ? \Carbon\Carbon::parse($endTimeSlot->scheduleTime->scheduled_end_time)->format('H:i')
                        : null;

                    return [
                        'id' => $booking->id,
                        'customer_name' => $booking->customer->name ?? 'Unknown',
                        'customer_avatar' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                        'service_name' => $booking->items->map(function ($item) {
                            return $item->service->service_name ?? 'Unknown';
                        })->implode(' + '),
                        'barber_id' => $booking->barber_id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'time_display' => $startTime && $endTime ? "{$startTime} - {$endTime}" : 'N/A',
                        'status' => $booking->status,
                        'color_code' => $this->getStatusColor($booking->status),
                    ];
                });

            // 5. Group bookings under each barber
            $barbersWithBookings = $barbers->map(function ($barber) use ($allBookings) {
                $barber['bookings'] = $allBookings->where('barber_id', $barber->id)->values();
                return $barber;
            });

            $data = [
                'selected_date' => $date,
                'barbers' => $barbersWithBookings,
            ];

            return $this->success($data, 'Salon agenda retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('something went wrong', $e->getMessage());
        }
    }

    /**
     * Get UI color code based on booking status.
     */



    public function salonBarbarBookingDetails($id)
    {
        try {
            $user = Auth::guard('api')->user();


            if (!$user || $user->role !== 'salon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only salons can access these details.'
                ], 403);
            }

            $booking = Booking::query()->where('id', $id)
                ->with(['customer', 'barber', 'items.service', 'slots.scheduleTime'])
                ->where('salon_id', $user->id)
                ->first();

            if (!$booking) {
                return $this->success([],'Booking not found');
            }

            // 1. Format Service & Price
            $serviceNames = $booking->items->map(function ($item) {
                return $item->service->service_name ?? 'Unknown';
            })->implode(' + ');

            // 2. Format Date, Time & Duration
            $startTimeSlot = $booking->slots->sortBy(function($slot) {
                return optional($slot->scheduleTime)->scheduled_start_time;
            })->first();

            $totalDuration = 0;
            foreach ($booking->slots as $slot) {
                if ($slot->scheduleTime) {
                    $start = \Carbon\Carbon::parse($slot->scheduleTime->scheduled_start_time);
                    $end = \Carbon\Carbon::parse($slot->scheduleTime->scheduled_end_time);
                    $totalDuration += $start->diffInMinutes($end);
                }
            }

            $formattedDate = \Carbon\Carbon::parse($booking->booking_date)->format('l, F j, Y');
            $formattedTime = $startTimeSlot && $startTimeSlot->scheduleTime
                ? \Carbon\Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A')
                : 'N/A';

            $data = [
                'id' => $booking->id,
                'status' => ucfirst($booking->status),
                'status_color' => $this->getStatusColor($booking->status),

                'customer' => [
                    'id' => $booking->customer->id ?? null,
                    'name' => $booking->customer->name ?? 'Unknown',
                    'phone' => $booking->customer->phone ?? 'N/A',
                    'avatar' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                ],

                'booking_info' => [
                    'service_name' => $serviceNames,
                    'total_price' => (float)$booking->total_price,
                    'currency' => '€',
                    'location_type' => 'At the salon',
                    'date' => $formattedDate,
                    'time_and_duration' => "{$formattedTime} (Duration: {$totalDuration} min)",
                ],

                'barber' => [
                    'id' => $booking->barber->id ?? null,
                    'name' => $booking->barber->name ?? 'N/A',
                    'role' => 'Team Member',
                    'avatar' => $booking->barber->profile_image ? asset($booking->barber->profile_image) : null,
                ]
            ];

            return $this->success($data, 'Salon barber booking details retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('something went wrong', $e->getMessage());
        }
    }









    private function getStatusColor($status)
    {
        return match ($status) {
            'completed' => '#e6f7ef', // Greenish
            'confirmed' => '#e6f7ef', // Greenish
            'accepted' => '#fef9e7',  // Yellowish
            'pending' => '#f9f9f9',   // Greyish
            'cancelled' => '#fdeded', // Reddish
            default => '#f9f9f9',
        };
    }

}
