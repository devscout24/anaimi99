<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AvailablityDay;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceProvideController extends Controller
{
    use ApiResponse;

    public function serviceIndex()
    {
        try {
            $services = Service::all();

            return $this->success($services, 'Service is working');
        } catch (\Exception $e) {
            return $this->error(
                null,
                'Something went wrong',
                500
            );
        }
    }

    public function servicePriceIndex()
    {
        try {
            $servicesPrices = ServicePrice::where('created_for_type','barber')->get();

            return $this->success($servicesPrices, 'Service Price list fetched successfully');
        } catch (\Throwable $e) {

            Log::error('Service Price Error: ' . $e->getMessage());

            return $this->error(
                null,
                'Something went wrong while fetching service prices',
                500
            );
        }
    }


    public function availabilityIndex()
    {
        try {
            $availabilityDays = AvailablityDay::query()
                ->select('id', 'day_name')
                ->distinct()
                ->orderBy('day_name')
                ->get();

            return $this->success($availabilityDays, 'Availability days fetched successfully');
        } catch (\Throwable $e) {

            Log::error('Availability Days Error: ' . $e->getMessage());

            return $this->error(
                null,
                'Something went wrong while fetching availability days',
                500
            );
        }

    }


}

