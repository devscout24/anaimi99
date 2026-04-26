<?php

use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ServiceProvideController;
use App\Http\Controllers\ServiceController as ControllersServiceController;
use App\Http\Controllers\ServicePriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/serviceIndex', [ServiceController::class, 'serviceIndex']);
// Route::get('/servicePriceIndex', [ServiceController::class, 'servicePriceIndex']);


Route::get('admin/serviceIndex', [ServiceProvideController::class, 'serviceIndex']);
Route::get('admin/servicePriceIndex', [ServiceProvideController::class, 'servicePriceIndex']);

Route::get('admin/availabilityDays', [ServiceProvideController::class, 'availabilityIndex']);

