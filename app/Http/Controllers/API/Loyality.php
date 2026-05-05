<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyalityAdd;

use App\Models\LoyaltySetting;
use App\Models\SalonLoyality;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Loyality extends Controller
{
    public function getLoyalityHistory(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();

            $entries = $user->customer_loyality()
                ->with(['booking.items.service', 'salon', 'barber'])
                ->orderBy('created_at', 'desc')
                ->get();

                $data=[];

            foreach($entries as $entry){
                $data[]=[
                    'id'=>$entry->id,
                    'points'=>$entry->remaining_loyality_point,
                    'type'=>$entry->type,
                    'booking_id'=>$entry->booking_id,
                    'salon_name'=>$entry->salon ? $entry->salon->name : null,
                    'barber_name'=>$entry->barber ? $entry->barber->name : null,
                    'services' => $entry->booking && $entry->booking->items
                        ? $entry->booking->items
                            ->map(function ($item) {
                                return $item->service ? $item->service->name : null;
                            })
                            ->filter()
                            ->implode(' + ')
                        : null,
                        'date'=>Carbon::parse($entry->booking->booking_date)->format('F d, Y'),
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


   public function getSalonWiseLoyaltyPoints($salonId)
    {
        try {
            $user = auth()->guard('api')->user();

            $loyaltyPoints = LoyalityAdd::query()
                ->where('salon_id', $salonId)
                ->where('customer_id', $user->id)
                ->latest()
                ->first();

                $userRole = User::query()->where('id', $salonId)->value('role');
              if($userRole ==="salon"){
                $salonLoyality=SalonLoyality::query()->where('salon_id', $salonId)->first();
                }
                else{
                    $salonLoyality=LoyaltySetting::query()->first();
                }
            return response()->json([
                'status' => 'success',
                'loyalty_points' => $loyaltyPoints->remaining_loyality_point ?? 0,
                'salon_loyalty' => $salonLoyality ? $salonLoyality->loyalty_points : 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}
