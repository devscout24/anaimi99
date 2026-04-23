<?php

namespace App\Http\Controllers\Backend\Setting;

use App\Helpers\MiaHelper;
use Illuminate\Http\Request;
use App\Models\SocialSetting;
use App\Http\Controllers\Controller;

class SocialSettingController extends Controller
{
    public function edit()
    {
        $setting = SocialSetting::first();
        return view('backend.layouts.settings.social_settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'facebook_link'  => 'nullable|string',
            'instagram_link' => 'nullable|string',
            'twitter_link'   => 'nullable|string',
            'tiktok_link'    => 'nullable|string',
            'whatsapp_link'  => 'nullable|string',
            'linkedin_link'  => 'nullable|string',
            'telegram_link'  => 'nullable|string',
            'youtube_link'   => 'nullable|string',

            // icons
            'facebook_icon'  => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'instagram_icon' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'twitter_icon'   => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'tiktok_icon'    => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'whatsapp_icon'  => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'linkedin_icon'  => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'telegram_icon'  => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'youtube_icon'   => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
        ]);

        $setting = SocialSetting::first() ?? new SocialSetting();

        // folder name
        $folder = 'social-icons';

        // ------------ Image Uploads Using MiaHelper --------------

        if ($request->hasFile('facebook_icon')) {
            $setting->facebook_icon = MiaHelper::updateFile($setting->facebook_icon, $request->file('facebook_icon'), $folder);
        }

        if ($request->hasFile('instagram_icon')) {
            $setting->instagram_icon = MiaHelper::updateFile($setting->instagram_icon, $request->file('instagram_icon'), $folder);
        }

        if ($request->hasFile('twitter_icon')) {
            $setting->twitter_icon = MiaHelper::updateFile($setting->twitter_icon, $request->file('twitter_icon'), $folder);
        }

        if ($request->hasFile('tiktok_icon')) {
            $setting->tiktok_icon = MiaHelper::updateFile($setting->tiktok_icon, $request->file('tiktok_icon'), $folder);
        }

        if ($request->hasFile('whatsapp_icon')) {
            $setting->whatsapp_icon = MiaHelper::updateFile($setting->whatsapp_icon, $request->file('whatsapp_icon'), $folder);
        }

        if ($request->hasFile('linkedin_icon')) {
            $setting->linkedin_icon = MiaHelper::updateFile($setting->linkedin_icon, $request->file('linkedin_icon'), $folder);
        }

        if ($request->hasFile('telegram_icon')) {
            $setting->telegram_icon = MiaHelper::updateFile($setting->telegram_icon, $request->file('telegram_icon'), $folder);
        }

        if ($request->hasFile('youtube_icon')) {
            $setting->youtube_icon = MiaHelper::updateFile($setting->youtube_icon, $request->file('youtube_icon'), $folder);
        }

        // ------------ Social Links --------------

        $setting->facebook_link  = $request->facebook_link;
        $setting->instagram_link = $request->instagram_link;
        $setting->twitter_link   = $request->twitter_link;
        $setting->tiktok_link    = $request->tiktok_link;
        $setting->whatsapp_link  = $request->whatsapp_link;
        $setting->linkedin_link  = $request->linkedin_link;
        $setting->telegram_link  = $request->telegram_link;
        $setting->youtube_link   = $request->youtube_link;

        $setting->save();

        return redirect()->back()->with('success', 'Social settings updated successfully!');
    }
}
