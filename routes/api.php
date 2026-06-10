<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FcmTokenController;
use App\Http\Controllers\API\ReviewController;

Broadcast::routes(['middleware' => ['api', 'auth:api']]);



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



Route::middleware(['auth:api', 'check.approval'])->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/user/profile/set', 'userProfileSet');
        Route::post('/profile/image/update', 'ProfileImageUpdate');

        Route::post('/change/password', 'changePassword');

        Route::post('/delete/account', 'deleteAccount');
        Route::post('/user/profile/get', 'userProfileGet');
        Route::post('/user/profile/update', 'userProfileUpdate');
    });

    Route::post('/fcm/token', [FcmTokenController::class, 'store']);
    Route::post('/fcm/token/delete', [FcmTokenController::class, 'destroy']);
});
require __DIR__ . '/Shahin.php';

require __DIR__ . '/api_abdullah.php';
