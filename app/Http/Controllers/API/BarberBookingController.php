<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                ->where('booking_date', $date);

            $this->hideExpiredAsapRequests($bookings, $user->id);

            $bookings = $bookings
                ->get();

            $formattedBookings = $bookings->map(function ($booking) {
                [$startTime, $endTime] = $this->getBookingTimeRange($booking, 'H:i');

                // Join service names
                $serviceNames = $booking->items->map(function ($item) {
                    return $item->service->service_name ?? 'Unknown';
                })->implode(' + ');

                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer->name ?? 'Unknown',
                    'customer_image' => $booking->customer?->profile_image
                                        ? asset($booking->customer->profile_image)
                                        : null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'services' => $serviceNames,
                    'status' => $booking->status,
                    'booking_type' => $booking->booking_type,
                    'payment_status'=>$booking->payment_status??null,
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

            $booking = Booking::with(['customer.provider_profiles', 'slots.scheduleTime', 'items.service'])
                ->where('barber_id', $user->id)
                ->where('id', $id);

            $this->hideExpiredAsapRequests($booking, $user->id);

            $booking = $booking
                ->first();

            if (!$booking) {
                return $this->notFound([], 'Booking not found.');
            }

            [$startTime, $endTime] = $this->getBookingTimeRange($booking);

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
                            'id' => $booking->customer?->id,
                            'name' => $booking->customer?->name ?? 'Unknown',
                            'phone' => $booking->customer?->phone,
                            'image' => $booking->customer?->profile_image
                                ? asset($booking->customer->profile_image)
                                : null,
                        ],
                'location' => [
                    'address' => $booking->customer->provider_profiles->salon_address ?? 'Address not specified',
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

            $user->load('provider_profiles');
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
                [$startTime,] = $this->getBookingTimeRange($booking);
                if (!$startTime) return false;

                $bookingDate = Carbon::parse($booking->booking_date)->format('Y-m-d');
                $bookingDateTime = Carbon::parse($bookingDate . ' ' . $startTime->format('H:i:s'));

                return $bookingDateTime->isAfter($now);
            })->sortBy(function ($booking) {
                [$startTime,] = $this->getBookingTimeRange($booking);
                $startTimeStr = $startTime ? $startTime->format('H:i:s') : '00:00:00';

                return Carbon::parse($booking->booking_date)->format('Y-m-d') . ' ' . $startTimeStr;
            })->first();

            $nextAppointmentData = null;
            if ($nextBooking) {
                [$startTime, $endTime] = $this->getBookingTimeRange($nextBooking, 'g:i A');

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

            // 3. New Requests (live ASAP requests and other pending bookings)
            $newRequests = Booking::with(['customer', 'items.service'])
                ->where('barber_id', $user->id)
                ->whereIn('status', ['search_barber', 'pending']);

            $this->hideExpiredAsapRequests($newRequests, $user->id);

            $newRequests = $newRequests
                ->get()
                ->map(function ($booking) {
                    $canUseActions = $booking->status === 'pending'
                        || ($booking->booking_type === 'as_soon_possible' && $booking->status === 'search_barber');

                    $serviceNames = $booking->items->map(function ($item) {
                        return optional($item->service)->service_name ?? 'Unknown';
                    })->implode(' + ');

                    return [
                        'id' => $booking->id,
                        'customer_name' => $booking->customer->name ?? 'Unknown',
                        'customer_image' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                        'services' => $serviceNames,
                        'cust_lat' => $booking->customer->latitude ?? null,
                        'cust_lng' => $booking->customer->longitude ?? null,
                        'time' => Carbon::parse($booking->created_at)->format('g:i A'),
                        'date' => Carbon::parse($booking->booking_date)->format('M d, Y'),
                        'status' => $booking->status,
                        'booking_type' => $booking->booking_type,
                        'can_accept' => $canUseActions,
                        'can_reject' => $canUseActions,
                    ];
                });

            $data = [
                'hello_message' => "Bonjour, " . explode(' ', $user->name)[0] . "!",
                'profile_image' => $user->profile_image ? asset($user->profile_image) : null,
                'availability' => (bool) ($user->availability ?? optional($user->provider_profiles)->available ?? false),
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
                [$startTime, $endTime] = $this->getBookingTimeRange($booking, 'g:i A');

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

    private function hideExpiredAsapRequests($query, int $barberId): void
    {
        $expiredAt = Carbon::now()->subMinutes(3);

        $query->where(function ($q) use ($expiredAt, $barberId) {
            $q->where('booking_type', '!=', 'as_soon_possible')
                ->orWhereNotIn('status', ['search_barber', 'pending'])
                ->orWhere('last_assigned_at', '>', $expiredAt)
                ->orWhereNotExists(function ($assignment) use ($barberId) {
                    $assignment->select(DB::raw(1))
                        ->from('booking_assign_histories')
                        ->whereColumn('booking_assign_histories.booking_id', 'bookings.id')
                        ->where('booking_assign_histories.barber_id', $barberId)
                        ->where('booking_assign_histories.status', 'pending');
                });
        });
    }

    private function getBookingTimeRange(Booking $booking, ?string $format = null): array
    {
        $sortedSlots = $booking->slots->sortBy(function ($slot) {
            return $this->getSlotStartTime($slot)?->format('H:i:s') ?? '99:99:99';
        });

        $startTime = $this->getSlotStartTime($sortedSlots->first());
        $endTime = $this->getSlotEndTime($sortedSlots->last());

        if (!$format) {
            return [$startTime, $endTime];
        }

        return [
            $startTime ? $startTime->format($format) : null,
            $endTime ? $endTime->format($format) : null,
        ];
    }

    private function getSlotStartTime($slot): ?Carbon
    {
        if (!$slot) {
            return null;
        }

        $time = optional($slot->scheduleTime)->scheduled_start_time ?? $slot->start_time;

        return $time ? Carbon::parse($time) : null;
    }

    private function getSlotEndTime($slot): ?Carbon
    {
        if (!$slot) {
            return null;
        }

        $time = optional($slot->scheduleTime)->scheduled_end_time ?? $slot->end_time;

        return $time ? Carbon::parse($time) : null;
    }


    public function bookingPaymentStatus($id)
    {
        try {
            $user = Auth::guard("api")->user();

            $booking = Booking::where('customer_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$booking) {
                return $this->notFound([], 'Booking not found.');
            }

            return $this->success(['payment_status' => $booking->payment_status], 'Payment status fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
        }
    }

}
