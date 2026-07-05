<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');
Route::get('/login', LoginController::class)->name('login');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
