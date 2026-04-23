<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Farhad\StatusController;
use App\Http\Controllers\Backend\Farhad\ProductController;
use App\Http\Controllers\Backend\Farhad\CategoryController;
use App\Http\Controllers\Backend\Farhad\DashboardController;
use App\Http\Controllers\Backend\Setting\MailSettingController;
use App\Http\Controllers\Backend\Setting\SocialSettingController;
use App\Http\Controllers\Backend\Setting\StripeSettingController;
use App\Http\Controllers\Backend\Setting\SystemSettingController;
use App\Http\Controllers\Backend\Setting\ProfileSettingController;

Route::middleware(['auth:web'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard route
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Categories routes
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Products routes
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Profile settings routes
    Route::get('profile/settings', [ProfileSettingController::class, 'edit'])->name('profile-settings.edit');
    Route::post('profile/settings/{id}', [ProfileSettingController::class, 'update'])->name('profile-settings.update');

    // Social settings routes
    Route::get('social/settings', [SocialSettingController::class, 'edit'])->name('social-settings.edit');
    Route::post('social/settings', [SocialSettingController::class, 'update'])->name('social-settings.update');

    // Mail settings routes
    Route::get('mail/settings', [MailSettingController::class, 'edit'])->name('mail-settings.edit');
    Route::post('mail/settings', [MailSettingController::class, 'update'])->name('mail-settings.update');

    // Stripe Settings routes
    Route::get('stripe/settings', [StripeSettingController::class, 'edit'])->name('stripe-settings.edit');
    Route::post('stripe/settings', [StripeSettingController::class, 'update'])->name('stripe-settings.update');

    // Systems routes
    Route::get('system/settings', [SystemSettingController::class, 'edit'])->name('system-settings.edit');
    Route::post('system/settings', [SystemSettingController::class, 'update'])->name('system-settings.update');

    //Status
    Route::post('/update-status', [StatusController::class, 'update'])->name('status.update');
});
