<?php

use App\Http\Controllers\Agent\AgentRegistrationController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/joinagent/{referralCode}', [AgentRegistrationController::class, 'redirectLegacyReferral'])
    ->where('referralCode', '[A-Za-z0-9]{8}')
    ->name('join-agent.legacy');
Route::get('/joinus/{referralCode}', [AgentRegistrationController::class, 'createFromReferral'])
    ->where('referralCode', '[A-Za-z0-9]{8}')
    ->name('join-agent.create');
Route::get('/joinus/{referralCode}/login-id-availability', [AgentRegistrationController::class, 'checkLoginIdAvailability'])
    ->where('referralCode', '[A-Za-z0-9]{8}')
    ->middleware('throttle:30,1')
    ->name('join-agent.login-id-availability');
Route::post('/joinus/{referralCode}', [AgentRegistrationController::class, 'storeFromReferral'])
    ->where('referralCode', '[A-Za-z0-9]{8}')
    ->middleware('throttle:10,1')
    ->name('join-agent.store');
