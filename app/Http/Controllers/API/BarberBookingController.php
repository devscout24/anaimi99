<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarberBookingController extends Controller
{
    use ApiResponse;

    /**
     * Get booking list for the authenticated barber.
     */
    public function bookingList(Request $request)
    {
        try {
            $user = Auth::guard("api")->user();
            $date = $request->date ? Carbon::parse($request->date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

            $bookings = Booking::with(['customer', 'slots.scheduleTime', 'items.service'])
                ->where('barber_id', $user->id)
                ->where('booking_date', $date)
                ->get();
            
            $formattedBookings = $bookings->map(function ($booking) {
                // Sort slots by start time
                $sortedSlots = $booking->slots->sortBy(function ($slot) {
                    return $slot->scheduleTime->scheduled_start_time;
                });

                $startTime = $sortedSlots->first() ? Carbon::parse($sortedSlots->first()->scheduleTime->scheduled_start_time)->format('H:i') : null;
                $endTime = $sortedSlots->last() ? Carbon::parse($sortedSlots->last()->scheduleTime->scheduled_end_time)->format('H:i') : null;

                // Join service names
                $serviceNames = $booking->items->map(function ($item) {
                    return $item->service->service_name ?? 'Unknown';
                })->implode(' + ');

                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer->name ?? 'Unknown',
                    'customer_image' => $booking->customer->profile_image ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'services' => $serviceNames,
                    'status' => $booking->status,
                    'booking_type' => $booking->booking_type,
                    'total_price' => $booking->total_price,
                ];
            });

            return $this->success($formattedBookings, 'Booking list fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
        }
    }

    /**
     * Get detailed information for a specific booking.
     */
    public function bookingDetails($id)
    {
        try {
            $user = Auth::guard("api")->user();

            $booking = Booking::with(['customer.providerprofiles', 'slots.scheduleTime', 'items.service'])
                ->where('barber_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$booking) {
                return $this->notFound([], 'Booking not found.');
            }

            // Sort slots
            $sortedSlots = $booking->slots->sortBy(function ($slot) {
                return $slot->scheduleTime->scheduled_start_time;
            });

            $firstSlot = $sortedSlots->first();
            $lastSlot = $sortedSlots->last();

            $startTime = $firstSlot ? Carbon::parse($firstSlot->scheduleTime->scheduled_start_time) : null;
            $endTime = $lastSlot ? Carbon::parse($lastSlot->scheduleTime->scheduled_end_time) : null;

            $duration = 0;
            if ($startTime && $endTime) {
                $duration = $startTime->diffInMinutes($endTime);
            }

            $serviceNames = $booking->items->map(function ($item) {
                return $item->service->service_name ?? 'Unknown';
            })->implode(' + ');

            $data = [
                'id' => $booking->id,
                'status' => ucfirst($booking->status),
                'service_names' => $serviceNames,
                'total_price' => number_format($booking->total_price, 2) . ' €',
                'booking_type' => str_replace('_', ' ', ucfirst($booking->booking_type)),
                'formatted_date' => Carbon::parse($booking->booking_date)->format('l, F j, Y'),
                'formatted_time' => $startTime ? $startTime->format('g:i A') : null,
                'duration_text' => $duration . ' minutes',
                'client' => [
                    'id' => $booking->customer->id ?? null,
                    'name' => $booking->customer->name ?? 'Unknown',
                    'phone' => $booking->customer->phone ?? null,
                    'image' => $booking->customer->profile_image ?? null,
                ],
                'location' => [
                    'address' => $booking->customer->providerprofiles->salon_address ?? 'Address not specified',
                    'latitude' => $booking->latitude ?? $booking->customer->latitude,
                    'longitude' => $booking->longitude ?? $booking->customer->longitude,
                ],
                'services_details' => $booking->items->map(function ($item) {
                    return [
                        'id' => $item->service_id,
                        'name' => $item->service->service_name ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }),
            ];

            return $this->success($data, 'Booking details fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
        }
    }
}
