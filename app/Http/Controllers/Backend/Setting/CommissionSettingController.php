<?php

namespace App\Http\Controllers\Backend\Setting;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use Illuminate\Http\Request;

class CommissionSettingController extends Controller
{
    public function edit()
    {
        $salonCommission = CommissionSetting::where('type', 'salon')->first();
        $homeBarberCommission = CommissionSetting::where('type', 'home_barber')->first();

        return view('backend.layouts.settings.commission_settings', compact(
            'salonCommission',
            'homeBarberCommission'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'salon_commission_rate' => 'required|numeric|min:0|max:100',
            'home_barber_commission_rate' => 'required|numeric|min:0|max:100',
            'salon_description' => 'nullable|string|max:255',
            'home_barber_description' => 'nullable|string|max:255',
        ]);

        try {
            CommissionSetting::updateOrCreate(
                ['type' => 'salon'],
                [
                    'commission_rate' => $request->salon_commission_rate,
                    'description' => $request->salon_description,
                ]
            );

            CommissionSetting::updateOrCreate(
                ['type' => 'home_barber'],
                [
                    'commission_rate' => $request->home_barber_commission_rate,
                    'description' => $request->home_barber_description,
                ]
            );

            return back()->with('success', 'Commission settings updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update commission settings.');
        }
    }
}
