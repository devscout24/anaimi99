<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ReviewRating;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Services\FcmService;
use Stripe\Stripe;
use Stripe\Refund;
use Illuminate\Support\Facades\Validator;

class CustomerReservationController extends Controller
{
    use ApiResponse;
    public function customerReservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:upcoming,past,cancelled',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }
        try {
            $user = Auth::guard('api')->user();
            if ($user->role !== "customer") {
                return $this->error([], "Only customer can view reservations", 403);
            }
            $query = Booking::with(['barber', 'salon', 'items.service', 'slots.scheduleTime'])
                ->where('customer_id', $user->id);
            if ($request->type == "upcoming") {
                $query->whereIn('status', ['pending', 'confirmed', 'accepted'])
                    ->where('booking_date', '>=', Carbon::now()->format('Y-m-d'));
            } elseif ($request->type == "past") {
                $query->where('status', 'completed');
            } elseif ($request->type == "cancelled") {
                $query->where('status', 'cancelled');
            }
            $bookings = $query->orderBy('booking_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            $formattedBookings = $bookings->map(function ($booking) {

                $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + ');

                [$startTime, $endTime] = $this->getBookingTimeRange($booking, 'g:i A');
                $formattedTime = $startTime && $endTime ? $startTime . ' - ' . $endTime : ($startTime ?? '');
                $totalServicePrice = $booking->items->sum(fn($item) => (float) ($item->total ?? 0));
                $avgRating = ReviewRating::where('barbar_id', $booking->barber_id)->avg('rating') ?: 0;
                // Date formatting (e.g., "Thu, Oct 24")
                $dateFormatted = Carbon::parse($booking->booking_date)->isToday() ? 'Today' : Carbon::parse($booking->booking_date)->format('D, M d');
                return [
                    'id' => $booking->id,
                    'service_name' => $serviceNames ?: 'Service',
                    'total_service_price' => number_format($totalServicePrice, 2, '.', ''),
                    'location_type' => $booking->salon_id ? 'At the salon' : 'At home',
                    'location_name' => $booking->salon->business_name ?? $booking->address ?? 'N/A',
                    'barber_image' => $booking->barber?->profile_image ? asset($booking->barber->profile_image) : null,
                    'average_rating' => number_format($avgRating, 1, '.', ''),
                    'date' => Carbon::parse($booking->booking_date)->format('Y-m-d'),
                    'slot_time' => $formattedTime,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'date_time' => $dateFormatted . ', ' . $formattedTime,
                    'status' => ucfirst($booking->status),
                    'can_track' => (!$booking->salon_id && $booking->status == 'confirmed'),
                ];
            });
            return $this->success($formattedBookings, 'Reservation list fetched successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }


    public function customerReservationDetails($id)
    {
        try {
            $user = Auth::guard('api')->user();
            if ($user->role !== "customer") {
                return $this->success([], "Only customer can view reservations", 422);
            }
            $booking = Booking::with(['barber', 'salon', 'items.service', 'slots.scheduleTime'])->where('id', $id)->first();
            if (!$booking) {
                return $this->success([], "Booking not found", 422);
            }

            $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + ');

            [$startTime, $endTime] = $this->getBookingTimeRange($booking, 'g:i A');
            $formattedTime = $startTime && $endTime ? $startTime . ' - ' . $endTime : ($startTime ?? '');
            $totalServicePrice = $booking->items->sum(fn($item) => (float) ($item->total ?? 0));
            $avgRating = ReviewRating::where('barbar_id', $booking->barber_id)->avg('rating') ?: 0;
            // Date formatting (e.g., "Thu, Oct 24")
            $dateFormatted = Carbon::parse($booking->booking_date)->isToday() ? 'Today' : Carbon::parse($booking->booking_date)->format('D, M d');
            return $this->success([
                'id' => $booking->id,
                'service_name' => $serviceNames ?: 'Service',
                'total_service_price' => number_format($totalServicePrice, 2, '.', ''),
                'location_type' => $booking->salon_id ? 'At the salon' : 'At home',
                'location_name' => $booking->salon->business_name ?? $booking->address ?? 'N/A',
                'location_lat' => $booking->salon?->latitude ?? $booking->barber?->latitude,
                'location_lon' => $booking->salon?->longitude ?? $booking->barber?->longitude,
                'barber_image' => $booking->barber?->profile_image ? asset($booking->barber->profile_image) : null,
                'average_rating' => number_format($avgRating, 1, '.', ''),
                'date' => Carbon::parse($booking->booking_date)->format('Y-m-d'),
                'slot_time' => $formattedTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'date_time' => $dateFormatted . ', ' . $formattedTime,
                'salon_details' => $booking->salon ? [
                    'id' => $booking->salon->id,
                    'name' => $booking->salon->business_name,
                    'address' => $booking->salon->address,
                    'latitude' => $booking->salon->latitude,
                    'longitude' => $booking->salon->longitude,
                ] : null,

                'barber_details' => $booking->barber ? [
                    'id' => $booking->barber->id,
                    'name' => $booking->barber->name,
                    'image' => $booking->barber->profile_image ? asset($booking->barber->profile_image) : null,
                    'average_rating' => number_format($avgRating, 1, '.', ''),
                    'latitude' => $booking->barber->latitude,
                    'longitude' => $booking->barber->longitude,
                ] : null,
                'status' => ucfirst($booking->status),
                'can_track' => (!$booking->salon_id && $booking->status == 'confirmed'),
            ], 'Reservation details fetched successfully');
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }



    public function customerbookingDetails($id)
    {
        try {
            $user = Auth::guard('api')->user();
            if ($user->role !== "customer") {
                return $this->error([], "Only customer can view reservations", 403);
            }

            $booking = Booking::with(['barber', 'salon', 'items.service', 'slots.scheduleTime'])
                ->where('id', $id)
                ->where('customer_id', $user->id)
                ->first();

            if (!$booking) {
                return $this->error([], "Booking not found", 404);
            }

            // Barber Info
            $barber = $booking->barber;
            $avgRating = ReviewRating::where('barbar_id', $booking->barber_id)->avg('rating') ?: 5.0;
            $barberInfo = [
                'name' => $barber->name ?? 'N/A',
                'image' => $barber?->profile_image ? asset($barber->profile_image) : null,
                'rating' => number_format($avgRating, 1),
                'services' => $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + '),
            ];

            // Service Info
            $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + ');
            [$startTime, $endTime] = $this->getBookingTimeRange($booking, 'g:i A');
            $formattedTime = $startTime && $endTime ? $startTime . ' - ' . $endTime : ($startTime ?? '');
            $dateFormatted = Carbon::parse($booking->booking_date)->isToday() ? 'Today' : Carbon::parse($booking->booking_date)->format('D, M d');

            // Tracking Steps
            $status = strtolower($booking->status);
            $tracking = [
                [
                    'title' => 'Request sent',
                    'sub_title' => 'Your request has been successfully submitted.',
                    'is_completed' => true,
                ],
                [
                    'title' => 'Request accepted',
                    'sub_title' => ($barber->name ?? 'Barber') . ' has accepted your booking.',
                    'is_completed' => in_array($status, ['accepted', 'confirmed', 'on_the_way', 'arrived', 'completed']),
                ],
                [
                    'title' => 'On the way',
                    'sub_title' => 'The barber is heading to your address.',
                    'is_completed' => in_array($status, ['on_the_way', 'arrived', 'completed']),
                ],
                [
                    'title' => 'Arrived',
                    'sub_title' => 'The barber has arrived on site.',
                    'is_completed' => in_array($status, ['arrived', 'completed']),
                ],
                [
                    'title' => 'Terminée',
                    'sub_title' => 'The service has been completed.',
                    'is_completed' => ($status == 'completed'),
                ],
            ];

            return $this->success([
                'barber' => $barberInfo,
                'booking_info' => [
                    'id' => $booking->id,
                    'service_name' => $serviceNames ?: 'Service',
                    'location_type' => $booking->salon_id ? 'At the salon' : 'At home',
                    'location_name' => $booking->salon->business_name ?? $booking->address ?? 'N/A',
                    'location_lat' => $booking->salon?->latitude ?? $barber?->latitude,
                    'location_lon' => $booking->salon?->longitude ?? $barber?->longitude,
                    'date' => Carbon::parse($booking->booking_date)->format('Y-m-d'),
                    'slot_time' => $formattedTime,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'date_time' => $dateFormatted . ', ' . $formattedTime,
                    'total_service_price' => number_format($booking->items->sum(fn($item) => (float) ($item->total ?? 0)), 2, '.', ''),
                    'total_to_pay' => number_format($booking->total_price, 2) . ' €',
                    'status' => ucfirst($booking->status),
                ],
                'tracking' => $tracking,
                'chat_id' => $barber->id ?? null,
            ], 'Booking details fetched successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }


    public function customerCancelReservation($id)
    {
        try {
            $user = Auth::guard('api')->user();
            if ($user->role !== "customer") {
                return $this->success([], "Only customer can cancel reservations",);
            }

            $booking = Booking::with(['slots.scheduleTime'])
                ->where('id', $id)
                ->where('customer_id', $user->id)
                ->whereIn('status', ['pending', 'confirmed', 'accepted'])
                ->first();

            if (!$booking) {
                return $this->success([], "Booking not found or cannot be cancelled");
            }

            // Determine booking start datetime from earliest slot
            $startTimeSlot = $booking->slots->sortBy(function ($slot) {
                return optional($slot->scheduleTime)->scheduled_start_time;
            })->first();

            if ($startTimeSlot && $startTimeSlot->scheduleTime && $startTimeSlot->scheduleTime->scheduled_start_time) {
                $startStr = $startTimeSlot->scheduleTime->scheduled_start_time;
                $startDateTime = null;
                try {
                    if (is_string($startStr) && (strpos($startStr, ' ') !== false || strpos($startStr, 'T') !== false)) {
                        // scheduled_start_time already contains a date+time
                        $startDateTime = Carbon::parse($startStr);
                    } else {
                        // scheduled_start_time is time-only, combine with booking_date's date part
                        $bookingDate = Carbon::parse($booking->booking_date)->toDateString();
                        $startDateTime = Carbon::parse($bookingDate . ' ' . $startStr);
                    }
                } catch (Exception $e) {
                    try {
                        $startDateTime = Carbon::parse($startStr);
                    } catch (Exception $e2) {
                        $startDateTime = null;
                    }
                }

                if ($startDateTime) {
                    $deadline = (clone $startDateTime)->subHour();
                    if (Carbon::now()->greaterThanOrEqualTo($deadline)) {
                        return $this->success([], 'Booking cannot be cancelled less than 1 hour before start time');
                    }
                }
            }

            // If booking was paid online, attempt refund
            if (strtolower($booking->payment_status) === 'paid') {
                $payment = Payment::where('booking_id', $booking->id)->latest()->first();
                if ($payment && ($payment->payment_type === 'online' || $payment->payment_type === 'custom')) {
                    if ($payment->transaction_id) {
                        try {
                            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                            // Create refund against payment_intent or charge depending on what's stored
                            $refundParams = [];
                            // prefer payment_intent
                            $refundParams['payment_intent'] = $payment->transaction_id;

                            $refund = Refund::create($refundParams);

                            // mark payment refunded
                            $payment->payment_status = 'refunded';
                            $payment->save();

                            $booking->payment_status = 'refunded';
                        } catch (\Exception $e) {
                            return $this->error([], 'Refund failed: ' . $e->getMessage(), 500);
                        }
                    } else {
                        return $this->error([], 'Cannot refund: no transaction id stored for this booking', 500);
                    }
                }
            }

            $booking->status = 'cancelled';
            $booking->save();

            // Notify Barber about cancellation
            if ($booking->barber_id) {
                FcmService::sendNotification(
                    $booking->barber_id,
                    'Booking Cancelled',
                    'The customer has cancelled their booking.',
                    ['booking_id' => $booking->id]
                );
            }

            // Notify Salon if applicable
            if ($booking->salon_id && $booking->salon_id != $booking->barber_id) {
                FcmService::sendNotification(
                    $booking->salon_id,
                    'Booking Cancelled',
                    'A booking for your salon has been cancelled by the customer.',
                    ['booking_id' => $booking->id]
                );
            }

            return $this->success([], 'Booking cancelled successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
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
}
