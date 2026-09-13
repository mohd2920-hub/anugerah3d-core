<?php

use App\Http\Controllers\Agent\AgentRegistrationController;
use App\Http\Controllers\Agent\DashboardController;
use App\Http\Controllers\Agent\HistoryController;
use App\Http\Controllers\Agent\LoginController;
use App\Http\Controllers\Agent\OrderController;
use App\Http\Controllers\Agent\PosController;
use App\Http\Controllers\Agent\ProfileController;
use App\Http\Controllers\Agent\ProgressController;
use App\Http\Controllers\Agent\TeamController;
use App\Http\Controllers\Agent\WeeklyPerformanceController;
use App\Http\Controllers\CustomerCatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('/c/{referrer}', [CustomerCatalogueController::class, 'show'])->middleware(['signed', 'throttle:120,1'])->name('customer.catalogue');
Route::post('/c/{referrer}/orders', [CustomerCatalogueController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('customer.store');
Route::get('/customer-order/{token}/receipt', [CustomerCatalogueController::class, 'receipt'])->middleware('throttle:60,1')->name('customer.receipt');
Route::get('/customer-order/{token}', [CustomerCatalogueController::class, 'status'])->middleware('throttle:60,1')->name('customer.status');
Route::post('/customer-order/{token}/proof', [CustomerCatalogueController::class, 'proof'])->middleware('throttle:10,1')->name('customer.proof');

Route::middleware(['signed', 'throttle:20,1'])->group(function (): void {
    Route::get('/register-agent/{referrer}', [AgentRegistrationController::class, 'create'])->name('registration.create');
    Route::post('/register-agent/{referrer}', [AgentRegistrationController::class, 'store'])->name('registration.store');
});

Route::middleware('guest:agent')->group(function (): void {
    Route::redirect('/', '/login')->name('home');
    Route::get('/login', LoginController::class)->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth:agent')->group(function (): void {
    Route::get('/customer-commissions/{order}', [CustomerCatalogueController::class, 'commissionDetail'])->whereNumber('order')->name('customer.commissions.show');
    Route::get('/customer-commissions/{order}/proof/{entry}', [CustomerCatalogueController::class, 'commissionProof'])->whereNumber('order')->whereNumber('entry')->name('customer.commissions.proof');
    Route::get('/customer-commissions', [CustomerCatalogueController::class, 'commissions'])->name('customer.commissions');
    Route::get('/customer-catalogue-qr', [CustomerCatalogueController::class, 'qr'])->name('customer.qr');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/products', [DashboardController::class, 'cataloguePage'])->name('dashboard.products');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/history', HistoryController::class)->name('history');
    Route::redirect('/products', '/dashboard');
    Route::redirect('/catalogue', '/dashboard');
    Route::get('/progress', ProgressController::class)->name('progress');
    Route::get('/weekly-performance', [WeeklyPerformanceController::class, 'index'])->name('weekly-performance.index');
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/team/{teamAgent}', [TeamController::class, 'show'])->name('team.show');
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/sign-in', [PosController::class, 'signIn'])->name('pos.sign-in');
    Route::post('/pos/sign-out', [PosController::class, 'signOut'])->name('pos.sign-out');
    // Some mobile/PWA restores revisit the last form action as a GET request.
    Route::get('/pos/sales', fn () => redirect()->route('agent.pos.index', ['tab' => 'history'], 303));
    Route::post('/pos/sales', [PosController::class, 'store'])->name('pos.sales.store');
    Route::post('/pos/sales/{posSale}/receipt', [PosController::class, 'sendReceipt'])->name('pos.sales.receipt');
    Route::get('/pos/sales/{posSale}/edit', [PosController::class, 'edit'])->name('pos.sales.edit');
    Route::put('/pos/sales/{posSale}', [PosController::class, 'update'])->name('pos.sales.update');
    Route::delete('/pos/sales/{posSale}', [PosController::class, 'destroy'])->name('pos.sales.destroy');
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::post('/customer-order/{token}/confirm-receipt', [CustomerCatalogueController::class, 'confirmReceipt'])->middleware('throttle:10,1')->name('customer.confirm-receipt');
