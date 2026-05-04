<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ReviewRating;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $query = Booking::with(['salon', 'items.service', 'slots.scheduleTime'])
                ->where('customer_id', $user->id);
            if ($request->type == "upcoming") {
                $query->whereIn('status', ['pending', 'confirmed','accepted'])
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

                $startTimeSlot = $booking->slots->sortBy(function ($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                })->first();

                $formattedTime = '';
                if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                    $formattedTime = Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A');
                }
                // Date formatting (e.g., "Thu, Oct 24")
                $dateFormatted = Carbon::parse($booking->booking_date)->isToday() ? 'Today' : Carbon::parse($booking->booking_date)->format('D, M d');
                return [
                    'id' => $booking->id,
                    'service_name' => $serviceNames ?: 'Service',
                    'location_type' => $booking->salon_id ? 'At the salon' : 'At home',
                    'location_name' => $booking->salon->business_name ?? $booking->address ?? 'N/A',
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


  public function customerReservationDetails($id){
    try{
        $user = Auth::guard('api')->user();
        if ($user->role !== "customer") {
            return $this->success([], "Only customer can view reservations", 422);
        }
        $booking = Booking::with(['salon', 'items.service', 'slots.scheduleTime'])->where('id',$id)->first();
        if(!$booking){
            return $this->success([], "Booking not found", 422);
        }

        $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + ');

        $startTimeSlot = $booking->slots->sortBy(function ($slot) {
            return optional($slot->scheduleTime)->scheduled_start_time;
        })->first();

        $formattedTime = '';
        if ($startTimeSlot && $startTimeSlot->scheduleTime) {
            $formattedTime = Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A');
        }
        // Date formatting (e.g., "Thu, Oct 24")
        $dateFormatted = Carbon::parse($booking->booking_date)->isToday() ? 'Today' : Carbon::parse($booking->booking_date)->format('D, M d');
        return $this->success([
            'id' => $booking->id,
            'service_name' => $serviceNames ?: 'Service',
            'location_type' => $booking->salon_id ? 'At the salon' : 'At home',
            'location_name' => $booking->salon->business_name ?? $booking->address ?? 'N/A',
            'location_lat'=>$booking->salon?->latitude??$booking->barbar?->latitude,
            'location_lon'=>$booking->salon?->longitude??$booking->barbar?->longitude,
            'date_time' => $dateFormatted . ', ' . $formattedTime,
            'status' => ucfirst($booking->status),
            'can_track' => (!$booking->salon_id && $booking->status == 'confirmed'),
        ], 'Reservation details fetched successfully');

    }
    catch(Exception $e){
        return $this->error([],$e->getMessage(), 500);
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
            $avgRating = ReviewRating::where('barbar_id', $booking->barbar_id)->avg('rating') ?: 5.0;
            $barberInfo = [
                'name' => $barber->name ?? 'N/A',
                'image' => $barber->profile_image ? asset($barber->profile_image) : null,
                'rating' => number_format($avgRating, 1),
                'services' => $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + '),
            ];

            // Service Info
            $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->filter()->implode(' + ');
            $startTimeSlot = $booking->slots->sortBy(fn($s) => optional($s->scheduleTime)->scheduled_start_time)->first();
            $formattedTime = $startTimeSlot && $startTimeSlot->scheduleTime ? Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A') : '';
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
                    'date_time' => $dateFormatted . ', ' . $formattedTime,
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


}
