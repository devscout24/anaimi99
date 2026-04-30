<?php

use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AsSoonAsPossibleBookingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankstatementController;
use App\Http\Controllers\Api\BarberBookingController;
use App\Http\Controllers\Api\BarberschedulebookingController;
use App\Http\Controllers\API\BookingActionController;
use App\Http\Controllers\Api\CustomerBookingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SalonBarberAddController;
use App\Http\Controllers\Api\SalonCustomBookingController;

use App\Http\Controllers\Api\SalonOrBarberController;
use App\Http\Controllers\Api\SalonPriceSetcontroller;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;





// Webhook route must be outside auth middleware
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);




Route::middleware('auth:api')->group(function () {


Route::controller(ProfileController::class)->group(function () {

    Route::post('/profile/update','update');
    Route::get('/user/profile/get', 'userProfileGet');
    Route::post('/user/galery/image/delete/{id}', 'userGaleryImageDelete');

    Route::post('/user/available/controll', 'availableControll');

    Route::post('user/location/update', 'updateLocation');
    Route::get('/user/business/details/get', 'userBusinessDetailsGet');
    Route::post('/user/business/details/update', 'userBusinessDetailsUpdate');
});

Route::controller(BankstatementController::class)->group(function () {
    Route::post('/bank/statement/add/update', 'addBankStatement');
    Route::get('/bank/statement/get', 'getBankStatement');
});
///////////////////common///////////////
Route::controller(ScheduleController::class)->group(function () {
    Route::post('/schedule/add', 'addSchedule');
    Route::get('/schedule/get', 'getSchedule');
    Route::post('/schedule/delete/{id}', 'deleteSchedule');
    Route::post('schedule/block/slot', 'blockSlot');
    Route::post('schedule/unblock/slot', 'unblockSlot');

    Route::get('barber/salon/slots/{id}', 'barberSalonSlots');


    Route::get('barber/salon/slots/with/block/booking', 'barberSalonSlotsWithBooking');
});

// ////////////////////Home barber///////////////////////////////

Route::controller(BarberBookingController::class)->group(function () {
    Route::post('barber/home/booking/list','bookingList');
    Route::get('barber/home/booking/details/{id}','bookingDetails');

    Route::get('')
});


//////////////////////salon/////////////////////////////////
  Route::controller(SalonBarberAddController::class)->group(function () {
    Route::post('/salon/barber/add', 'addSalonBarber');
    Route::get('/salon/barber/list', 'SalonBarberlist');
    Route::post('/salon/barber/delete/{id}', 'deleteSalonBarber');
    Route::get('barber/{id}', 'getBarberDetails');


    Route::get('send/temporary/password/email/{id}', 'SendTempPassBarbar');
  });

  Route::controller(SalonPriceSetcontroller::class)->group(function () {
    Route::post('/salon/price/set', 'addSalonPrice');
    Route::get('/salon/price/get', 'getSalonPrice');
    Route::post('/salon/price/delete/{id}', 'deleteSalonPrice');
    Route::post('/salon/price/update/{id}', 'updateSalonPrice');
  });

 Route::controller(SalonCustomBookingController::class)->group(function () {
    Route::post('/salon/custom/booking', 'salonCustomBooking');


 });




////////////////////////////////////////customer///////////////////////

//////////////////////Salon or Barber – Location Based/////////////////////
Route::controller(SalonOrBarberController::class)->group(function () {
    // List: GET /salon-or-barber?latitude=&longitude=&search=&type=&available=1&radius=20&per_page=15
    Route::post('salon-or-barber', 'index');

    // Details: GET /salon-or-barber/{id}?latitude=&longitude=
    Route::get('salon-or-barber/{id}', 'show');
});

Route::controller(BarberschedulebookingController::class)->group(function () {
    Route::post('booking/barber/slots', 'getSlots');
});


Route::controller(CustomerBookingController::class)->group(function () {
    Route::post('customer/booking/slots', 'bookingslotscustomer');
});

Route::post('customer/booking/as-soon-as-possible', [AsSoonAsPossibleBookingController::class, 'asSoonAsPossibleBooking']);

Route::controller(BookingActionController::class)->group(function () {
    Route::post('barber/booking/accept/{id}', 'acceptAsapBooking');
    Route::post('barber/booking/reject/{id}', 'rejectAsapBooking');
});


//////////////////////Admin Panel/////////////////////
Route::controller(AdminPaymentController::class)->group(function () {
    Route::get('/admin/payments', 'index');
    Route::get('/admin/payments/{id}', 'show');
    Route::post('/admin/payments/{id}/status', 'updatePaymentStatus');
    Route::get('/admin/commission/settings', 'getCommissionSettings');
    Route::post('/admin/commission/settings', 'setCommissionSetting');
});


});
