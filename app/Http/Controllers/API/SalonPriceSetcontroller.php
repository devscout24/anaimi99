<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServicePrice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalonPriceSetcontroller extends Controller
{

    use ApiResponse;
    public function addSalonPrice(Request $request)
    {

        $validator = Validator::make($request->all(), [
                    'service_id'     => 'required',
                    'price'            => 'required|numeric',
                    // 'created_for_type' => 'required|in:salon,barber',
                    'time_duration'=> 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }

        try {
              if(Auth::user()->role != "salon"){
                 return $this->error('You are not authorized to add service price');
              }
            $servicePrice = new ServicePrice();
            $servicePrice->service_id = $request->service_id;
            $servicePrice->price = $request->price;
            $servicePrice->created_for_type = "salon";
            $servicePrice->time_duration = $request->time_duration;
            $servicePrice->created_by =Auth::guard('api')->user()->id;
            $servicePrice->discount = $request->discount;
            $servicePrice->save();

            return $this->success($servicePrice, 'Service Price added successfully');
        } catch (\Throwable $e) {
           return $this->error($e->getMessage());
        }
    }

    public function getSalonPrice(){

        try {
              if(Auth::user()->role != "salon"){
                 return $this->error('You are not authorized to get service price');
              }
            $servicePrice = ServicePrice::where('created_by',Auth::guard('api')->user()->id)->get();
            return $this->success($servicePrice, 'Service Price list fetched successfully');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteSalonPrice($id){
        try{
            $servicePrice = ServicePrice::find($id);

            if($servicePrice->created_by != Auth::guard('api')->user()->id){
                return $this->error('You are not authorized to delete this service price');
            }
            $servicePrice->delete();
            return $this->success($servicePrice, 'Service Price deleted successfully');
        }catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateSalonPrice(Request $request,$id){
        try{
            $validator = Validator::make($request->all(), [
                'service_id'     => 'required',
                'price'            => 'required|numeric',
                // 'created_for_type' => 'required|in:salon,barber',
                'time_duration'=> 'required',
    ]);

    if ($validator->fails()) {
        return $this->validationError($validator->errors()->first());
    }

    $servicePrice = ServicePrice::find($id);

    if($servicePrice->created_by != Auth::guard('api')->user()->id){
        return $this->error('You are not authorized to update this service price');
    }

    $servicePrice->service_id = $request->service_id;
    $servicePrice->price = $request->price;
    $servicePrice->time_duration = $request->time_duration;
    $servicePrice->discount = $request->discount;
    $servicePrice->save();

    return $this->success($servicePrice, 'Service Price updated successfully');
        }
        catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
