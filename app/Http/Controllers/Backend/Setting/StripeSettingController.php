<?php

namespace App\Http\Controllers\Backend\Setting;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class StripeSettingController extends Controller
{
    public function edit()
    {
        return view('backend.layouts.settings.stripe_settings');
    }

    public function update(Request $request)
    {
        $request->validate([
            'stripe_public_key' => 'required|string|max:255',
            'stripe_secret_key' => 'required|string|max:255',
        ]);

        try {
            $envPath = base_path('.env');
            $envContent = File::get($envPath);
            $lineBreak = "\n";

            $envContent = preg_replace([
                '/STRIPE_PUBLIC_KEY=(.*)\s?/',
                '/STRIPE_SECRET_KEY=(.*)\s?/',
            ], [
                'STRIPE_PUBLIC_KEY=' . $request->stripe_public_key . $lineBreak,
                'STRIPE_SECRET_KEY=' . $request->stripe_secret_key . $lineBreak,
            ], $envContent);

            File::put($envPath, $envContent);

            return back()->with('success', 'Stripe settings updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update Stripe settings.');
        }
    }
}
