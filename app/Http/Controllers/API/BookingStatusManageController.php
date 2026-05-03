<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LoyalityAdd;
use App\Models\LoyaltySetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingStatusManageController extends Controller
{
    use ApiResponse;
    public function changeStatus(Request $request ,$id){
        $validator=Validator::make($request->all(),[
            'status'=>'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>0,
                'message'=>$validator->errors()
            ]);
        }

        try{
        $booking=Booking::query()->where('id',$id)->first();

        $user=Auth::guard('api')->user();



        if($booking->salon_id == $user->id || $booking->barber_id == $user->id){
            $booking->status=$request->status;
            if($request->payment_status){
                $booking->payment_status=$request->payment_status;
            }
            $booking->save();
           return $this->success($booking,'Booking status changed successfully');
        }
       return $this->success([],'booking not found');
    }
    catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
}


public function completedBooking(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'booking_ids' => 'required|array|min:1',
        'booking_ids.*' => 'integer|exists:bookings,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()
        ], 422);
    }

    try {
        $userId = $request->user_id;
        $bookingIds = $request->booking_ids;

        // 🔥 fetch only user bookings
        $bookings = Booking::whereIn('id', $bookingIds)
            ->where('customer_id', $userId)
            ->get();

        // 🔥 ownership check
        if ($bookings->count() !== count($bookingIds)) {
            return $this->success([], 'Some bookings do not belong to this user');
        }

        $loyaltySetting = LoyaltySetting::first();
        $points = $loyaltySetting->per_booking_loyality ?? 0;

        foreach ($bookings as $booking) {


            // if (in_array($booking->status, ['cancelled', 'completed'])) {
            //     continue;
            // }


            $booking->update([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);


            $loyalty = LoyalityAdd::where('customer_id', $booking->customer_id)->first();

            if ($loyalty) {
                $loyalty->remaining_loyality_point =
                    ($loyalty->remaining_loyality_point ?? 0) + $points;

                $loyalty->per_booking_loyality_point = $points;

                $loyalty->save();
            } else {
                $loyalty = new LoyalityAdd();
                $loyalty->customer_id = $booking->customer_id;
                $loyalty->salon_id = $booking->salon_id;
                $loyalty->barber_id = $booking->barber_id;
                $loyalty->booking_id = $booking->id;
                $loyalty->per_booking_loyality_point = $points;
                $loyalty->remaining_loyality_point = $points;
                $loyalty->save();
            }
        }


        return $this->success($bookings, 'Bookings marked as completed successfully');

    } catch (\Exception $e) {
        return $this->error('something went wrong', $e->getMessage());
    }
}

    public function DetailsBooking($id)
    {
        try {
            $booking = Booking::query()->where('id', $id)
                ->with(['customer', 'barber', 'items.service', 'slots.scheduleTime', 'bookingLoyality'])
                ->first();

            if (!$booking) {
                return $this->error('Booking not found');
            }

            // 1. Format Service Text (Names + Total Duration)
            $serviceNames = $booking->items->map(function ($item) {
                return $item->service->service_name ?? 'Unknown';
            })->implode(' + ');

            // Calculate total duration from slots
            $totalDuration = 0;
            foreach ($booking->slots as $slot) {
                if ($slot->scheduleTime) {
                    $start = \Carbon\Carbon::parse($slot->scheduleTime->scheduled_start_time);
                    $end = \Carbon\Carbon::parse($slot->scheduleTime->scheduled_end_time);
                    $totalDuration += $start->diffInMinutes($end);
                }
            }
            $serviceText = "{$serviceNames} ({$totalDuration} min)";

            // 2. Format Date/Time Text
            $startTime = $booking->slots->sortBy(function($slot) {
                return optional($slot->scheduleTime)->scheduled_start_time;
            })->first();

            $formattedDateTime = 'N/A';
            if ($startTime && $startTime->scheduleTime) {
                $date = \Carbon\Carbon::parse($booking->booking_date)->format('D, M j, Y');
                $time = \Carbon\Carbon::parse($startTime->scheduleTime->scheduled_start_time)->format('g:i A');
                $formattedDateTime = "{$date} - {$time}";
            }

            // 3. Loyalty points added for this booking
            $pointsAdded = LoyaltySetting::query()->first();

            $data = [
                'id' => $booking->id,
                'title' => 'Service confirmed',
                'subtitle' => 'The service was successfully completed.',
                'loyalty_message' =>  $pointsAdded->per_booking_loyality,
                'service_info' => $serviceText,
                'date_time_info' => $formattedDateTime,
                'booking_status' => $booking->status,
                'payment_status' => $booking->payment_status,
            ];

            return $this->success($data, 'Booking details fetched successfully');

        } catch (\Exception $e) {
            return $this->error('something went wrong', $e->getMessage());
        }
    }



}
