<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ASAP Booking Timeout Check
Schedule::command('app:booking-check-asap-timeout')->everyMinute();
Schedule::command('app:booking-check-payment-timeout')->everyMinute();
