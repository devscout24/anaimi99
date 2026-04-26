<?php



use App\Http\Controllers\API\ServiceProvideController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::get('/serviceIndex', [ServiceController::class, 'serviceIndex']);
// Route::get('/servicePriceIndex', [ServiceController::class, 'servicePriceIndex']);

Route::middleware(['auth:api'])->prefix('admin')->name('admin.')->group(function () {

   Route::get('/serviceIndex', [ServiceProvideController::class, 'serviceIndex']);
   Route::get('/servicePriceIndex', [ServiceProvideController::class, 'servicePriceIndex']);

    Route::get('admin/availabilityDays', [ServiceProvideController::class, 'availabilityIndex']);

});
