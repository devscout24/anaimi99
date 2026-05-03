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

    /**
     * Get dashboard summary for the authenticated barber.
     */
    public function dashboard()
    {
        try {
            $user = Auth::guard("api")->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $user->load('providerprofiles');
            $today = Carbon::today()->format('Y-m-d');
            $now = Carbon::now();

            // 1. Appointments Today Count
            $appointmentsTodayCount = Booking::where('barber_id', $user->id)
                ->whereDate('booking_date', $today)
                ->whereIn('status', ['accepted', 'confirmed', 'completed'])
                ->count();

            // 2. Next Appointment
            $bookings = Booking::with(['customer', 'slots.scheduleTime', 'items.service'])
                ->where('barber_id', $user->id)
                ->whereDate('booking_date', '>=', $today)
                ->whereIn('status', ['accepted', 'confirmed'])
                ->get();

            $nextBooking = $bookings->filter(function ($booking) use ($now) {
                $sortedSlots = $booking->slots->sortBy(function ($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                });
                $firstSlot = $sortedSlots->first();
                if (!$firstSlot || !$firstSlot->scheduleTime) return false;

                $startTimeStr = $firstSlot->scheduleTime->scheduled_start_time;
                // Ensure it's a string for parsing if it's a Carbon instance
                $startTimeStr = ($startTimeStr instanceof Carbon) ? $startTimeStr->format('H:i:s') : $startTimeStr;

                $bookingDate = Carbon::parse($booking->booking_date)->format('Y-m-d');
                $bookingDateTime = Carbon::parse($bookingDate . ' ' . $startTimeStr);

                return $bookingDateTime->isAfter($now);
            })->sortBy(function ($booking) {
                $sortedSlots = $booking->slots->sortBy(function ($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                });
                $firstSlot = $sortedSlots->first();
                $startTimeStr = $firstSlot && $firstSlot->scheduleTime ? $firstSlot->scheduleTime->scheduled_start_time : '00:00:00';
                $startTimeStr = ($startTimeStr instanceof Carbon) ? $startTimeStr->format('H:i:s') : $startTimeStr;

                return Carbon::parse($booking->booking_date)->format('Y-m-d') . ' ' . $startTimeStr;
            })->first();

            $nextAppointmentData = null;
            if ($nextBooking) {
                $sortedSlots = $nextBooking->slots->sortBy(function ($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                });
                $firstSlot = $sortedSlots->first();
                $lastSlot = $sortedSlots->last();

                $startTime = null;
                $endTime = null;

                if ($firstSlot && $firstSlot->scheduleTime) {
                    $st = $firstSlot->scheduleTime->scheduled_start_time;
                    $startTime = Carbon::parse($st)->format('g:i A');
                }

                if ($lastSlot && $lastSlot->scheduleTime) {
                    $et = $lastSlot->scheduleTime->scheduled_end_time;
                    $endTime = Carbon::parse($et)->format('g:i A');
                }

                $nextAppointmentData = [
                    'id' => $nextBooking->id,
                    'customer_name' => $nextBooking->customer->name ?? 'Unknown',
                    'customer_image' => $nextBooking->customer->profile_image ? asset($nextBooking->customer->profile_image) : null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'service_name' => optional($nextBooking->items->first())->service->service_name ?? 'Unknown',
                    'status' => $nextBooking->status,
                ];
            }

            // 3. New Requests (ASAP search_barber or pending)
            $newRequests = Booking::with(['customer', 'items.service'])
                ->where('barber_id', $user->id)
                ->whereIn('status', ['search_barber', 'pending'])
                ->get()
                ->map(function ($booking) {
                    $serviceNames = $booking->items->map(function ($item) {
                        return optional($item->service)->service_name ?? 'Unknown';
                    })->implode(' + ');

                    return [
                        'id' => $booking->id,
                        'customer_name' => $booking->customer->name ?? 'Unknown',
                        'customer_image' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                        'services' => $serviceNames,
                        'location' => 'At home', // Default to at home as per design
                        'time' => Carbon::parse($booking->created_at)->format('g:i A'),
                        'date' => Carbon::parse($booking->booking_date)->format('M d, Y'),
                    ];
                });

            $data = [
                'hello_message' => "Hello, " . explode(' ', $user->name)[0] . "!",
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'availability' => (bool) ($user->availability ?? optional($user->providerprofiles)->available ?? false),
                'summary' => [
                    'appointments_today' => $appointmentsTodayCount,
                    'next_appointment_time' => $nextAppointmentData ? $nextAppointmentData['start_time'] : 'No more today',
                ],
                'new_requests' => $newRequests,
                'next_appointment' => $nextAppointmentData,
            ];

            return $this->success($data, 'Dashboard data fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
    }
}
 public function bookingHistory(Request $request)
    {
        try {
            $user = Auth::guard("api")->user();

            if (!$user) {
                return $this->error('User not authenticated');
            }

            $statusFilter = $request->status; // all, completed, cancelled

            // 1. Summary Statistics
            $totalCompleted = Booking::where('barber_id', $user->id)
                ->where('status', 'completed')
                ->count();

            $monthlyRevenue = Booking::where('barber_id', $user->id)
                ->where('status', 'completed')
                ->whereMonth('booking_date', Carbon::now()->month)
                ->whereYear('booking_date', Carbon::now()->year)
                ->sum('total_price');

            // 2. Booking List with Filters
            $query = Booking::with(['customer', 'slots.scheduleTime', 'items.service'])
                ->where('barber_id', $user->id);

            if ($statusFilter === 'completed') {
                $query->where('status', 'completed');
            } elseif ($statusFilter === 'cancelled') {
                $query->where('status', 'cancelled');
             }

            // else {
            //     // 'all' tab shows completed and cancelled usually in history
            //     $query->whereIn('status', ['completed', 'cancelled']);
            // }

            $query->orderBy('booking_date', 'desc');

            $perPage = $request->per_page;
            if ($perPage) {
                $bookings = $query->paginate($perPage);
                $items = $bookings->items();
            } else {
                $bookings = $query->get();
                $items = $bookings;
            }

            $formattedHistory = collect($items)->map(function ($booking) {
                // Sort slots
                $sortedSlots = $booking->slots->sortBy(function ($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                });

                $startTime = null;
                $endTime = null;

                if ($sortedSlots->first() && $sortedSlots->first()->scheduleTime) {
                    $startTime = Carbon::parse($sortedSlots->first()->scheduleTime->scheduled_start_time)->format('g:i A');
                }

                if ($sortedSlots->last() && $sortedSlots->last()->scheduleTime) {
                    $endTime = Carbon::parse($sortedSlots->last()->scheduleTime->scheduled_end_time)->format('g:i A');
                }

                $serviceNames = $booking->items->map(function ($item) {
                    return optional($item->service)->service_name ?? 'Unknown';
                })->implode(' + ');

                // Check if it's at home or salon
                $locationText = ($booking->booking_type === 'home_barber' || $booking->booking_type === 'as_soon_possible')
                    ? '(At Home)'
                    : '(At Salon)';

                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer->name ?? 'Unknown',
                    'customer_image' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                    'services' => $serviceNames . ' ' . $locationText,
                    'total_price' => number_format($booking->total_price, 2) . ' €',
                    'formatted_date' => Carbon::parse($booking->booking_date)->format('l, F j'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => $booking->status,
                ];
            });

            $data = [
                'summary' => [
                    'completed_count' => $totalCompleted,
                    'monthly_revenue' => number_format($monthlyRevenue, 2) . ' €',
                ],
                'history' => $formattedHistory,
            ];

            if ($perPage) {
                $data['pagination'] = [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'total' => $bookings->total(),
                ];
            }

            return $this->success($data, 'Booking history fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
        }
    }

}
