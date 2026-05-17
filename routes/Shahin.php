<?php

use App\Http\Controllers\API\AdminPaymentController;
use App\Http\Controllers\API\AsSoonAsPossibleBookingController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BankstatementController;
use App\Http\Controllers\API\BarberBookingController;
use App\Http\Controllers\API\BarberschedulebookingController;
use App\Http\Controllers\API\BookingActionController;
use App\Http\Controllers\API\BookingStatusManageController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\CustomerBookingController;
use App\Http\Controllers\API\CustomerReservationController;
use App\Http\Controllers\API\CustomerReviewRatingController;
use App\Http\Controllers\API\HelpandSupportController;
use App\Http\Controllers\API\InvoiceController;

use App\Http\Controllers\API\Loyality;
use App\Http\Controllers\API\LoyalityBookingController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\SalonBarberAddController;
use App\Http\Controllers\API\SalonBarberBookingScheduleList;
use App\Http\Controllers\API\SalonCustomBookingController;
use App\Http\Controllers\API\SalonInvoiceController;
use App\Http\Controllers\API\SalonLoyalityController;
use App\Http\Controllers\API\SalonOrBarberController;
use App\Http\Controllers\API\SalonPriceSetcontroller;
use App\Http\Controllers\API\SalonProfileController;
use App\Http\Controllers\Api\SalonServiceController;
use App\Http\Controllers\API\ScheduleController;

use App\Http\Controllers\API\StripeWebhookController;
use Illuminate\Support\Facades\Route;










// Webhook route must be outside auth middleware
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);




Route::middleware('auth:api')->group(function () {


    Route::controller(LoyalityBookingController::class)->group(function () {
        Route::post('/loyalty-booking', 'loyaltyBooking');
    });

    Route::controller(ProfileController::class)->group(function () {

        Route::post('/profile/update', 'update');
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
        Route::post('barber/home/booking/list', 'bookingList');

        Route::get('barber/home/booking/details/{id}', 'bookingDetails');

        Route::get('barber/home/dashboard', 'dashboard');
        Route::get('barber/home/booking/history', 'bookingHistory');
    });

    Route::controller(BookingStatusManageController::class)->group(function () {
        Route::post('booking/status/change/{id}', 'changeStatus');

        Route::post('/booking/completed/by', 'completedBooking');
        Route::get('booking/completed/by/{id}', 'DetailsBooking');
    });

    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'index');
        Route::get('invoice/details/{id}', 'details');
        Route::get('invoice/download/{id}', 'download');
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


   Route::controller(SalonServiceController::class)->group(function () {
        Route::post('/salon/service/add', 'addSalonService');
        Route::get('/salon/service/list', 'salonServiceList');
        Route::post('/salon/service/delete/{id}', 'deleteSalonService');

    });







    Route::controller(SalonCustomBookingController::class)->group(function () {
        Route::post('/salon/custom/booking', 'salonCustomBooking');
    });



    Route::controller(SalonLoyalityController::class)->group(function () {
        Route::post('/salon/loyalty/point/add/update', 'addLoyaltyPoints');
        Route::get('/salon/loyalty/point/get', 'getLoyaltyPoints');
    });


    ////////salon barber wise booking schedule list///////////////
    Route::controller(SalonBarberBookingScheduleList::class)->group(function () {

        Route::get('salon/barber/booking/schedule/list', 'salonBarberBookingScheduleList');

        Route::get('salon/barbar/booking/details/{id}', 'salonBarbarBookingDetails');
    });

    Route::get('salon/report', [\App\Http\Controllers\API\ReportController::class, 'index']);

    Route::controller(SalonProfileController::class)->group(function () {
        Route::get('salon/profile/{type}', 'getProfile');
    });






    ////////////////////////////////////////customer///////////////////////


    Route::controller(CustomerReservationController::class)->group(function () {
        Route::get('customer/reservation', 'customerReservation');
        Route::get('customer/reservation/details/{id}', 'customerReservationDetails');
        Route::get('customer/booking/details/{id}', 'customerbookingDetails');

        Route::post('customer/reservation/cancel/{id}', 'customerCancelReservation');
    });


    Route::controller(Loyality::class)->group(function () {

        Route::get('loyalty/point/get/history', 'getLoyalityHistory');

        Route::get('loyalty/point/get', 'getLoyaltyPoint');

        Route::get('/salon/wise/loyalty/point/get/{salonId}', 'getSalonWiseLoyaltyPoints');
    });


    Route::controller(LoyalityBookingController::class)->group(function () {
        Route::post('loyalty/booking', 'loyaltyBooking');
    });

    Route::controller(HelpandSupportController::class)->group(function () {
      Route::post('help/support', 'submitHelpSupport');
    });


  Route::controller(SalonServiceController::class)->group(function () {
        Route::get('customer/salon/service/list/{salon_id}', 'customerSalonServiceList');
        Route::get('customer/barber/service/list', 'customerBarberServiceList');
    });



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


    Route::controller(CustomerReviewRatingController::class)->group(function () {
        Route::post('customer/review/rating', 'customerReviewRating');
        Route::get('customer/review/rating', 'customerReviewRatingList');
        Route::get('customer/review/rating/{id}', 'customerReviewRatingDetails');
        Route::post('customer/review/rating/{id}', 'customerReviewRatingUpdate');
    });

    Route::controller(SalonInvoiceController::class)->group(function () {
        Route::get('salon/invoices', 'index');
        Route::get('salon/invoices/{id}', 'details');
        Route::get('salon/invoices/download/{id}', 'download');

        // New Periodic (Bi-monthly) Routes
        Route::get('salon/period-invoices', 'periodIndex');
        Route::get('salon/period-invoices/details/{id}', 'periodDetails');
        Route::get('salon/period-invoices/download/{id}', 'periodDownload');
    });

    //////////////////////Admin Panel/////////////////////
    Route::controller(AdminPaymentController::class)->group(function () {
        Route::get('/admin/payments', 'index');
        Route::get('/admin/payments/{id}', 'show');
        Route::post('/admin/payments/{id}/status', 'updatePaymentStatus');
        Route::get('/admin/commission/settings', 'getCommissionSettings');
        Route::post('/admin/commission/settings', 'setCommissionSetting');
    });


      Route::controller(ChatController::class)->group(function () {
        Route::post('/chat/send', 'sendMessage');
        Route::get('/chat/mark/read/{conversation_id}', 'markAsRead');
        Route::get('/chat/get/{conversation_id}', 'getConversation');
        Route::get('chat/list/data', 'getchatlist');
        Route::get('/chat/delete/{chat_id}', 'chatdelete');
        Route::get('/chat/image/delete/{image_id}', 'chatImageDelete');
    });






});
