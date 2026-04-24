<?php

use App\Http\Controllers\Backend\abdullah\ServiceController;
use App\Http\Controllers\Backend\abdullah\ServicePriceController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:web'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('services/index', [ServiceController::class, 'index'])->name('services.index');
    Route::post('services/store', [ServiceController::class, 'store'])->name('services.store');

    Route::get('services/edit/{id}', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('services/update/{id}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('services/destroy/{id}', [ServiceController::class, 'destroy'])->name('services.destroy');

    Route::get('service-prices/index', [ServicePriceController::class, 'index'])->name('service-prices.index');
    Route::post('service-prices/store', [ServicePriceController::class, 'store'])->name('service-prices.store');
    Route::get('service-prices/edit/{id}', [ServicePriceController::class, 'edit'])->name('service-prices.edit');
    Route::put('service-prices/update/{id}', [ServicePriceController::class, 'update'])->name('service-prices.update');
    Route::delete('service-prices/destroy/{id}', [ServicePriceController::class, 'destroy'])->name('service-prices.destroy');

});
