<?php

use App\Http\Controllers\Backend\Admin\ClientManagementController;
use App\Http\Controllers\Backend\Admin\ReportController;
use App\Http\Controllers\Backend\Admin\ReportDownloadController;
use App\Http\Controllers\Backend\AdminChatController;
use App\Http\Controllers\Backend\Farhad\CategoryController;
use App\Http\Controllers\Backend\Farhad\DashboardController;
use App\Http\Controllers\Backend\Farhad\ProductController;
use App\Http\Controllers\Backend\Farhad\StatusController;
use App\Http\Controllers\Backend\Setting\CommissionSettingController;
use App\Http\Controllers\Backend\Setting\MailSettingController;
use App\Http\Controllers\Backend\Setting\ProfileSettingController;
use App\Http\Controllers\Backend\Setting\SocialSettingController;
use App\Http\Controllers\Backend\Setting\StripeSettingController;
use App\Http\Controllers\Backend\Setting\SystemSettingController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:web', 'set.locale'])->prefix('admin')->name('admin.')->group(function () {

    // Reports & Transactions
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('transactions', 'transactions')->name('transactions');
        Route::get('provider-reports', 'providerReports')->name('providers');
        Route::get('booking-report', 'bookingReport')->name('bookings');
        Route::get('revenue-report', 'revenueReport')->name('revenue');
        Route::get('analytics', 'analyticReports')->name('analytics');
        Route::get('loyalty-report', 'loyaltyReport')->name('loyalty');
        Route::get('download-pdf/{id}', [ReportDownloadController::class, 'downloadProviderPdf'])->name('download-pdf');
    });

    // Management Routes
    Route::prefix('manage-clients')->name('manage.')->group(function () {
        Route::get('salons', [ClientManagementController::class, 'manageSalons'])->name('salons');
        Route::get('barbers', [ClientManagementController::class, 'manageBarbers'])->name('barbers');
        Route::get('details/{id}', [ClientManagementController::class, 'getDetails'])->name('details');
        Route::post('update-status', [ClientManagementController::class, 'updateStatus'])->name('update-status');
    });

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

    // Commission Settings routes
    Route::get('commission/settings', [CommissionSettingController::class, 'edit'])->name('commission-settings.edit');
    Route::post('commission/settings', [CommissionSettingController::class, 'update'])->name('commission-settings.update');

    // Systems routes
    Route::get('system/settings', [SystemSettingController::class, 'edit'])->name('system-settings.edit');
    Route::post('system/settings', [SystemSettingController::class, 'update'])->name('system-settings.update');

    // Loyalty setting routes
    Route::get('loyalty-setting', [\App\Http\Controllers\Backend\Farhad\LoyaltySettingController::class, 'edit'])->name('loyalty-setting.edit');
    Route::put('loyalty-setting', [\App\Http\Controllers\Backend\Farhad\LoyaltySettingController::class, 'update'])->name('loyalty-setting.update');

    //Status
    Route::post('/update-status', [StatusController::class, 'update'])->name('status.update');


    Route::controller(AdminChatController::class)->group(function () {
        Route::get('/chat/view/blade', 'chatViewBlade')->name('chat.view');
        Route::get('/chat/admin/list', 'chatList')->name('chat.list');
        Route::get('/chat/fetch/admin/{receiver_id}', 'fetchConversation')->name('chat.fetch');
        Route::post('/chat/admin/send', 'sendMessage')->name('chat.send');
        Route::get('/chat/mark/read/admin/{conversation_id}', 'markAsRead')->name('chat.mark.read');
        Route::get('/chat/admin/delete/{chat_id}', 'chatDelete')->name('chat.delete');
        Route::get('/chat/admin/image/delete/{image_id}', 'chatImageDelete')->name('chat.image.delete');
    });
});

