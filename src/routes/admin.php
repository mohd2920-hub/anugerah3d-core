<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PasswordResetController;
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
});
