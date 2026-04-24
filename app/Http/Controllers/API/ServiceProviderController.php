<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    use ApiResponse;

    public function serviceIndex()
    {
        try {
            $services = Service::all();

            return $this->success($services, 'Service API is working');
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
            $servicesPrices = ServicePrice::all();

            return $this->success($servicesPrices, 'Service Price list fetched successfully');
        } catch (\Throwable $e) {

            \Log::error('Service Price Error: ' . $e->getMessage());

            return $this->error(
                null,
                'Something went wrong while fetching service prices',
                500
            );
        }
    }

    
}
