<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\BusinessSiteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SystemManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(function (): void {
    Route::redirect('/', '/login')->name('home');
    Route::get('/login', LoginController::class)->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    // Password reset routes
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}/{email}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth:admin')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::prefix('system')->name('system.')->group(function (): void {
        Route::get('/manage-data', [SystemManagementController::class, 'manageData'])->name('manage-data');
        Route::get('/activity-log', [SystemManagementController::class, 'activityLog'])->name('activity-log');
    });

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment.update');
    Route::patch('/orders/{order}/process', [OrderController::class, 'process'])->name('orders.process');
    Route::patch('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::resource('products', ProductController::class)->except('show');
    Route::resource('business-sites', BusinessSiteController::class)->except('show');
    Route::put('/agents/{agent}/profile-picture', [AgentController::class, 'updateProfilePicture'])->name('agents.profile-picture.update');
    Route::patch('/agents/{agent}/approve', [AgentController::class, 'approve'])->name('agents.approve');
    Route::put('/agents/{agent}/password', [AgentController::class, 'resetPassword'])->name('agents.password.update');
    Route::post('/agents/{agent}/resend-registration-info', [AgentController::class, 'resendRegistrationInfo'])->name('agents.registration-info.resend');
    Route::resource('agents', AgentController::class);
});
