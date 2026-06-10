<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('login'); //added redirect to login --- custom
});

Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('language.switch');

// Stripe Payment Status Routes
Route::get('/payment/success', [PaymentStatusController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [PaymentStatusController::class, 'cancel'])->name('payment.cancel');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// Backend (admin) web routes
require __DIR__ . '/backend_farhad.php';

// Additional admin routes (services, etc.)
require __DIR__ . '/abdullah.php';
