<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Get Data From Database
        View::composer('backend.*', function ($view) {
            $systemSetting = SystemSetting::first() ?? new SystemSetting([
                'logo' => 'default-logo.png',
                'mini_logo' => 'default-mini-logo.png',
                'favicon' => 'default-favicon.png',
                'system_title' => 'My System',
                'company_name' => 'My Company',
                'tag_line' => 'Best Company',
                'phone_number' => '017XXXXXXXX',
                'whatsapp_number' => '017XXXXXXXX',
                'email' => 'email@email.com',
                'copyright_text' => '2025 © Company. All rights reserved.',
            ]);

            $view->with('systemSetting', $systemSetting);
        });
    }
}
