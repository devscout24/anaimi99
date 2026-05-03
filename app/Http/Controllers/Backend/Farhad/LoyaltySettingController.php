<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Http\Controllers\Controller;
use App\Models\LoyaltySetting;
use Illuminate\Http\Request;

class LoyaltySettingController extends Controller
{
    public function edit()
    {
        $setting = LoyaltySetting::first();
        return view('backend.layouts.loyalty.setting', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'per_booking_loyality' => 'required|numeric|min:0',
        ]);

        LoyaltySetting::updateOrCreate(
            ['id' => 1],
            ['per_booking_loyality' => $request->per_booking_loyality]
        );

        return back()->with('success', 'Loyalty setting updated successfully!');
    }
}
