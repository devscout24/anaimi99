<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class SalonServiceController extends Controller
{
    use ApiResponse;
    public function addSalonService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'services'                    => 'required|array',
            'services.*.service_name'     => 'nullable|string',
            'services.*.service_id'       => 'nullable|exists:services,id',
            'services.*.created_for_type' => 'required|in:salon,barber',
            'services.*.price'            => 'required|numeric',
            'services.*.discount'         => 'nullable|numeric',
            'services.*.time_duration'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError(['errors' => $validator->errors()], 422);
        }

        // ✅ Unique check আলাদাভাবে
        foreach ($request->services as $serviceData) {
            if (!empty($serviceData['service_name'])) {
                $exists = Service::where('service_name', $serviceData['service_name'])
                    ->where('id', '!=', $serviceData['service_id'] ?? 0)
                    ->exists();

                if ($exists) {
                    return $this->validationError([
                        'errors' => [
                            'service_name' => ['The service name "' . $serviceData['service_name'] . '" already exists.']
                        ]
                    ], 422);
                }
            }
        }

        try {
            $createdServices = [];
            $updatedServices = [];

            foreach ($request->services as $serviceData) {

                if (!empty($serviceData['service_id'])) {
                    // ✅ UPDATE
                    $service = Service::find($serviceData['service_id']);

                    if (!$service) {
                        return $this->error(['error' => 'Service not found with id ' . $serviceData['service_id']], 404);
                    }

                    $updateData = ['salon_id' => Auth::guard("api")->user()->id];

                    if (!empty($serviceData['service_name'])) {
                        $updateData['service_name'] = $serviceData['service_name'];
                    }

                    $service->update($updateData);
                    $updatedServices[] = $service;
                } else {
                    // ✅ CREATE — service_name অবশ্যই লাগবে
                    if (empty($serviceData['service_name'])) {
                        return $this->error(['error' => 'service_name is required for new service'], 422);
                    }

                    $service = Service::create([
                        'service_name' => $serviceData['service_name'],
                        'salon_id'     => Auth::guard("api")->user()->id,
                    ]);

                    $createdServices[] = $service;
                }

                // ✅ ServicePrice update/create
                ServicePrice::updateOrCreate(
                    [
                        'service_id'       => $service->id,
                        'created_for_type' => $serviceData['created_for_type'],
                    ],
                    [
                        'price'         => $serviceData['price'],
                        'discount'      => $serviceData['discount'] ?? null,
                        'time_duration' => $serviceData['time_duration'] ?? null,
                        'created_by'    => Auth::guard("api")->user()->id,
                    ]
                );
            }

            $data = [
                'createdServices' => $createdServices,
                'updatedServices' => $updatedServices,
            ];

            return $this->success($data, "Service synced successfully");
        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 500);
        }
    }


    public function salonServiceList()
    {
        try {
            $salonId = Auth::guard("api")->user()->id;
            $services = Service::with('servicePrice')->where('salon_id', $salonId)->get();
            return $this->success($services, "service list retrieved successfully");
        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteSalonService($id)
    {
        try {
            $service = Service::find($id);
            if (!$service) {
                return $this->error(['error' => 'Service not found'], 404);
            }
            $service->delete();
            return $this->success(null, "Service deleted successfully");
        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 500);
        }
    }

    public function customerSalonServiceList($salon_id)
    {
        try {


            $services = Service::with('servicePrice')->where('salon_id', $salon_id)->get();
            return $this->success($services, "service list retrieved successfully");
        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 500);
        }
    }

    public function customerBarberServiceList()
    {
        try {
            $services = Service::with('servicePrice')->where('salon_id', null)->get();
            return $this->success($services, "service list retrieved successfully");
        } catch (\Exception $e) {
            return $this->error(['error' => $e->getMessage()], 500);
        }
    }
}
