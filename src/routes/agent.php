<?php

use App\Http\Controllers\Agent\DashboardController;
use App\Http\Controllers\Agent\HistoryController;
use App\Http\Controllers\Agent\LoginController;
use App\Http\Controllers\Agent\ProfileController;
use App\Http\Controllers\Agent\ProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:agent')->group(function (): void {
    Route::redirect('/', '/login')->name('home');
    Route::get('/login', LoginController::class)->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth:agent')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/history', HistoryController::class)->name('history');
    Route::redirect('/products', '/dashboard');
    Route::redirect('/catalogue', '/dashboard');
    Route::get('/progress', ProgressController::class)->name('progress');
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
