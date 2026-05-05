<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;



Route::controller(AuthController::class)->group(function () {
    // user login and logout
    Route::post('/user-login', 'login');
    Route::post('/signup', 'signup');
    Route::post('/user-logout', 'logout');



    Route::post('forget/password', 'forgetPassword');
    Route::post('otp/check', 'checkOtp');
    Route::post('reset/password', 'resetPassword');
    Route::post('/resend/otp', 'resendOtp');



});



Route::middleware('auth:api')->group(function () {

Route::controller(AuthController::class)->group(function () {
    Route::post('/user/profile/set', 'userProfileSet');
    Route::post('/profile/image/update','ProfileImageUpdate');

     Route::post('/change/password', 'changePassword');

     Route::post('/delete/account', 'deleteAccount');
     Route::post('/fcm/token', 'fcmToken');
     Route::post('/user/profile/get', 'userProfileGet');
     Route::post('/user/profile/update', 'userProfileUpdate');

});


});
require __DIR__ . '/shahin.php';

require __DIR__ . '/api_abdullah.php';
