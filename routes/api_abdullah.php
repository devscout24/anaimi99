<?php

use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ServiceProviderController;
use App\Http\Controllers\ServiceController as ControllersServiceController;
use App\Http\Controllers\ServicePriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/serviceIndex', [ServiceController::class, 'serviceIndex']);
// Route::get('/servicePriceIndex', [ServiceController::class, 'servicePriceIndex']);


Route::get('admin/serviceIndex', [ServiceProviderController::class, 'serviceIndex']);
Route::get('admin/servicePriceIndex', [ServiceProviderController::class, 'servicePriceIndex']);