<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use App\Models\SalonLoyality;
use Illuminate\Support\Facades\Auth;

class SalonLoyalityController extends Controller
{

use ApiResponse;
    public function addLoyaltyPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:users,id',
            'loyality_points' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try{

        $user= Auth::guard('api')->user();

        if($user->role != 'salon'){
            return $this->errors(['error' => 'Only salons can add loyalty points'],422);
        }

            $loyaltyAdd = SalonLoyality::updateOrCreate(
                ['service_id' => $request->service_id,

                 'salon_id' => $user->id,

                ],
                ['loyalty_points' => $request->loyality_points,
                'service_reach_loyality' => $request->service_reach_loyality ?? 0,
                ],

            );

            return $this->success($loyaltyAdd, 'Loyalty points added/updated successfully');
        }

        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


     public function getLoyaltyPoints()
    {
        try{
        $user= Auth::guard('api')->user();

        if($user->role != 'salon'){
            return $this->errors(['error' => 'Only salons can view loyalty points'],422);
        }

        $loyaltyPoints = SalonLoyality::query()->where('salon_id', $user->id)->get();

        return $this->success($loyaltyPoints, 'Loyalty points retrieved successfully');
    }

    catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}

