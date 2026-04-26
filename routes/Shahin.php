<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankstatementController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;





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





});
