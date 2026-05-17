<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LoyalityAdd;
use App\Models\LoyaltySetting;
use App\Models\SalonLoyality;
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
            'user_id' => 'required|exists:users,id', // This is Customer ID
            'booking_ids' => 'nullable|array|min:1',
            'booking_ids.*' => 'integer|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $customerId = $request->user_id;
            $bookingIds = $request->booking_ids;
            $authProvider = Auth::guard('api')->user();

            // =================================================================
            // 1. "Without Booking ID" Logic (Direct Scan/Point add)
            // =================================================================
            if (empty($bookingIds)) {
                $salonId = null;
                $barberId = null;
                $points = 0;

                if ($authProvider->role === 'salon') {
                    $salonId = $authProvider->id;
                    $setting = SalonLoyality::where('salon_id', $salonId)->first();
                    $points = $setting->per_booking_loyality ?? 0;
                } elseif ($authProvider->role === 'home_barbar') {
                    $barberId = $authProvider->id;
                    $setting = LoyaltySetting::first();
                    $points = $setting->per_booking_loyality ?? 0;
                } elseif ($authProvider->role === 'salon_barbar') {
                    $barberId = $authProvider->id;
                    $salonId = $authProvider->salon_id; // Get salon_id from user table

                    if ($salonId) {
                        $setting = SalonLoyality::where('salon_id', $salonId)->first();
                        $points = $setting->per_booking_loyality ?? 0;
                    } else {
                        $setting = LoyaltySetting::first();
                        $points = $setting->per_booking_loyality ?? 0;
                    }
                }

                $lastTotal = LoyalityAdd::where('customer_id', $customerId)
                    ->latest('id')
                    ->value('remaining_loyality_point') ?? 0;

                $loyalty = LoyalityAdd::create([
                    'customer_id' => $customerId,
                    'salon_id' => $salonId,
                    'barber_id' => $barberId,
                    'booking_id' => null, // No specific booking
                    'per_booking_loyality_point' => $points,
                    'remaining_loyality_point' => $lastTotal + $points,
                ]);

                return $this->success($loyalty, 'Loyalty points added successfully via scan (No Booking).');
            }

            // =================================================================
            // 2. "With Booking ID" Logic (Complete existing bookings)
            // =================================================================
            $bookings = Booking::query()->whereIn('id', $bookingIds)
                ->where('customer_id', $customerId)
                ->get();

            if ($bookings->isEmpty()) {
                return $this->error('No matching bookings found for this customer.', [], 404);
            }

            foreach ($bookings as $booking) {
                // Update booking status
                $booking->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                ]);

                // ❌ Skip point addition if this was already a loyalty redemption booking
                if ($booking->booking_type === 'loyalty') {
                    continue;
                }

                // Determine points and source based on specific booking data
                $points = 0;
                if (!empty($booking->salon_id)) {
                    // Booking belongs to a SALON
                    $setting = SalonLoyality::where('salon_id', $booking->salon_id)->first();
                    $points = $setting->per_booking_loyality ?? 0;
                } else {
                    // Booking belongs to a HOME BARBER
                    $setting = LoyaltySetting::first();
                    $points = $setting->per_booking_loyality ?? 0;
                }

                $currentLastTotal = LoyalityAdd::where('customer_id', $booking->customer_id)
                    ->latest('id')
                    ->value('remaining_loyality_point') ?? 0;

                LoyalityAdd::create([
                    'customer_id' => $booking->customer_id,
                    'salon_id' => !empty($booking->salon_id) ? $booking->salon_id : null,
                    'barber_id' => empty($booking->salon_id) ? $booking->barber_id : null,
                    'booking_id' => $booking->id,
                    'per_booking_loyality_point' => $points,
                    'remaining_loyality_point' => $currentLastTotal + $points,
                ]);
            }

            return $this->success($bookings, 'Bookings marked as completed and points added successfully.');

        } catch (\Exception $e) {
            return $this->error('Something went wrong', $e->getMessage());
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

           $barbarDetails=[
                      'barbar_name'=>$booking->barber->name ?? 'N/A',
                      'babrbar_profile'=>asset($booking->barber->profile_image) ?? 'N/A',
                      'phone'=>$booking->barber->phone ??'NA',
           ];




            $data = [
                'id' => $booking->id,
                'title' => 'Service confirmed',
                'subtitle' => 'The service was successfully completed.',
                'loyalty_message' =>  $pointsAdded->per_booking_loyality,
                'barbarDetails'=>$barbarDetails,

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