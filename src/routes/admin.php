<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AgentEmailTemplateController;
use App\Http\Controllers\Admin\BusinessSiteController;
use App\Http\Controllers\Admin\BusinessSiteOperationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SystemManagementController;
use App\Http\Controllers\Admin\WeeklyClosingController;
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
    Route::get('/orders/{order}/print-full', [OrderController::class, 'printFull'])->name('orders.print.full');
    Route::get('/orders/{order}/print-order', [OrderController::class, 'printOrder'])->name('orders.print.order');
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment.update');
    Route::patch('/orders/{order}/process', [OrderController::class, 'process'])->name('orders.process');
    Route::patch('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/weekly-closings', [WeeklyClosingController::class, 'index'])->name('weekly-closings.index');
    Route::get('/weekly-closings/{weeklyClosing}', [WeeklyClosingController::class, 'show'])->name('weekly-closings.show');
    Route::patch('/weekly-closings/{weeklyClosing}/agents/{agentSummary}/payment', [WeeklyClosingController::class, 'updatePayment'])->name('weekly-closings.payments.update');

    Route::resource('products', ProductController::class);
    Route::get('/business-site-operations/{businessSiteOperation}', [BusinessSiteOperationController::class, 'show'])->name('business-site-operations.show');
    Route::delete('/business-site-operations/{businessSiteOperation}', [BusinessSiteOperationController::class, 'destroy'])->name('business-site-operations.destroy');
    Route::patch('/business-sites/{businessSite}/start', [BusinessSiteController::class, 'start'])->name('business-sites.start');
    Route::patch('/business-sites/{businessSite}/stop', [BusinessSiteController::class, 'stop'])->name('business-sites.stop');
    Route::resource('business-sites', BusinessSiteController::class);
    Route::put('/agents/{agent}/profile-picture', [AgentController::class, 'updateProfilePicture'])->name('agents.profile-picture.update');
    Route::patch('/agents/{agent}/approve', [AgentController::class, 'approve'])->name('agents.approve');
    Route::put('/agents/{agent}/password', [AgentController::class, 'resetPassword'])->name('agents.password.update');
    Route::post('/agents/{agent}/resend-registration-info', [AgentController::class, 'resendRegistrationInfo'])->name('agents.registration-info.resend');
    Route::get('/agent-email-templates', [AgentEmailTemplateController::class, 'index'])->name('agent-email-templates.index');
    Route::get('/agent-email-templates/create', [AgentEmailTemplateController::class, 'create'])->name('agent-email-templates.create');
    Route::post('/agent-email-templates', [AgentEmailTemplateController::class, 'store'])->name('agent-email-templates.store');
    Route::get('/agent-email-templates/{agentEmailTemplate}/edit', [AgentEmailTemplateController::class, 'edit'])->name('agent-email-templates.edit');
    Route::put('/agent-email-templates/{agentEmailTemplate}', [AgentEmailTemplateController::class, 'update'])->name('agent-email-templates.update');
    Route::post('/agent-email-templates/{agentEmailTemplate}/send', [AgentEmailTemplateController::class, 'send'])->name('agent-email-templates.send');
    Route::resource('agents', AgentController::class);
});
