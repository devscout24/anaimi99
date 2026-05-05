<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Http\Controllers\Controller;
use App\Models\LoyaltySetting;
use App\Models\Service;
use Illuminate\Http\Request;

class LoyaltySettingController extends Controller
{
    public function edit()
    {
        $setting = LoyaltySetting::first();
        $services = Service::all();
        return view('backend.layouts.loyalty.setting', compact(['setting', 'services']));
    }

    public function update(Request $request)
    {
        $request->validate([
            'per_booking_loyality' => 'required|numeric|min:0',
            'service_id' => 'required|exists:services,id',
        ]);

        LoyaltySetting::updateOrCreate(
            ['id' => 1],
            [
                'service_id' => $request->service_id,
                'per_booking_loyality' => $request->per_booking_loyality,
                'service_reach_loyality' => $request->service_reach_loyality ?? 0,
            ]
        );

        return back()->with('success', 'Loyalty setting updated successfully!');
    }
}
