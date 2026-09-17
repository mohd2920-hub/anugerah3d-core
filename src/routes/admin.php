<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AgentDiscountSettingController;
use App\Http\Controllers\Admin\AgentEmailTemplateController;
use App\Http\Controllers\Admin\BusinessSiteController;
use App\Http\Controllers\Admin\BusinessSiteOperationController;
use App\Http\Controllers\Admin\BusinessSiteReportController;
use App\Http\Controllers\Admin\CustomerOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OperationClosureCorrectionController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProductBalanceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalaryManagementController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleCorrectionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffInvitationController;
use App\Http\Controllers\Admin\StaffSalaryDraftController;
use App\Http\Controllers\Admin\StaffSalaryPaymentController;
use App\Http\Controllers\Admin\SystemManagementController;
use App\Http\Controllers\Admin\WeeklyClosingController;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::get('/staff-invitation/{staff}/{token}', [StaffInvitationController::class, 'show'])->middleware('throttle:20,1')->name('invitation.show');
Route::post('/staff-invitation/{staff}/{token}', [StaffInvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitation.accept');

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

Route::middleware(['auth:admin', EnsureAdminAccess::class, AuthenticateSession::class])->group(function (): void {
    Route::get('/customer-orders/{customerOrder}/commission-proof/{entry}', [CustomerOrderController::class, 'commissionProof'])->name('customer-orders.commission-proof');
    Route::get('/customer-orders', [CustomerOrderController::class, 'index'])->name('customer-orders.index');
    Route::get('/customer-orders/{customerOrder}', [CustomerOrderController::class, 'show'])->name('customer-orders.show');
    Route::put('/customer-orders/{customerOrder}', [CustomerOrderController::class, 'update'])->name('customer-orders.update');
    Route::post('/customer-orders/{customerOrder}/payout', [CustomerOrderController::class, 'payout'])->name('customer-orders.payout');
    Route::get('/customer-orders/{customerOrder}/proof/{index}', [CustomerOrderController::class, 'proof'])->name('customer-orders.proof');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/salary-management', [SalaryManagementController::class, 'index'])->name('salary-management.index');
    Route::get('/salary-management/daily', [SalaryManagementController::class, 'index'])->name('salary-management.daily');
    Route::get('/salary-management/sessions', [StaffSalaryDraftController::class, 'index'])->name('salary-management.sessions');
    Route::post('/salary-management/sessions/preview', [StaffSalaryDraftController::class, 'preview'])->name('salary-management.sessions.preview');
    Route::post('/salary-management/sessions/{operation}/confirm', [StaffSalaryPaymentController::class, 'store'])->whereNumber('operation')->name('salary-management.confirm');
    Route::post('/salary-management/payments/{salaryPayment}/email', [StaffSalaryPaymentController::class, 'retry'])->middleware('throttle:6,1')->name('salary-management.email');
    Route::post('/salary-management/sessions', [StaffSalaryDraftController::class, 'store'])->name('salary-management.sessions.store');
    Route::post('/salary-management', [SalaryManagementController::class, 'store'])->name('salary-management.store');
    Route::get('/salary-management/summary', [SalaryManagementController::class, 'summary'])->name('salary-management.summary');
    Route::get('/salary-management/payments/{salaryPayment}', [SalaryManagementController::class, 'show'])->name('salary-management.show');
    Route::get('/salary-management/payments/{salaryPayment}/proof', [SalaryManagementController::class, 'proof'])->name('salary-management.proof');
    Route::view('/salary-management/settings', 'admin.salary-management.index', ['tab' => 'settings'])->name('salary-management.settings');
    Route::get('/salary-management/payslips', [SalaryManagementController::class, 'index'])->name('salary-management.payslips');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::prefix('system')->name('system.')->group(function (): void {
        Route::resource('staff', StaffController::class)->except(['show', 'destroy']);
        Route::post('staff/{staff}/invitation', [StaffController::class, 'resend'])->middleware('throttle:5,1')->name('staff.invitation');
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::get('/manage-data', [SystemManagementController::class, 'manageData'])->name('manage-data');
        Route::get('/activity-log', [SystemManagementController::class, 'activityLog'])->name('activity-log');
    });

    Route::put('/orders/discount-settings', [AgentDiscountSettingController::class, 'update'])->name('orders.discount-settings.update');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/print-full', [OrderController::class, 'printFull'])->name('orders.print.full');
    Route::get('/orders/{order}/print-order', [OrderController::class, 'printOrder'])->name('orders.print.order');
    Route::get('/sales/transactions', [SaleController::class, 'index'])->name('sales.transactions');
    Route::get('/sales/add', [SaleCorrectionController::class, 'selectOperation'])->name('sales.add');
    Route::resource('sales', SaleController::class)->only(['index', 'show']);
    Route::get('sales/{sale}/edit', [SaleCorrectionController::class, 'edit'])->name('sales.edit');
    Route::post('sales/{sale}/preview', [SaleCorrectionController::class, 'preview'])->name('sales.preview');
    Route::get('business-site-operations/{businessSiteOperation}/sales/create', [SaleCorrectionController::class, 'create'])->name('sales.create');
    Route::post('business-site-operations/{businessSiteOperation}/sales/preview', [SaleCorrectionController::class, 'previewMissing'])->name('sales.preview-missing');
    Route::post('sale-corrections', [SaleCorrectionController::class, 'store'])->name('sale-corrections.store');
    Route::patch('/orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment.update');
    Route::patch('/orders/{order}/process', [OrderController::class, 'process'])->name('orders.process');
    Route::patch('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/weekly-closings', [WeeklyClosingController::class, 'index'])->name('weekly-closings.index');
    Route::get('/weekly-closings/{weeklyClosing}', [WeeklyClosingController::class, 'show'])->name('weekly-closings.show');
    Route::patch('/weekly-closings/{weeklyClosing}/agents/{agentSummary}/payment', [WeeklyClosingController::class, 'updatePayment'])->name('weekly-closings.payments.update');

    Route::patch('/products/{product}/agent-visibility', [ProductController::class, 'toggleAgentVisibility'])->name('products.agent-visibility.toggle');
    Route::patch('/products/{product}/discontinuation', [ProductController::class, 'updateDiscontinuation'])->name('products.discontinuation.update');
    Route::get('/products/statistics', [ProductController::class, 'statistics'])->name('products.statistics');
    Route::get('/products/{product}/balance', [ProductBalanceController::class, 'show'])->name('products.balance.show');
    Route::patch('/products/{product}/balance', [ProductBalanceController::class, 'update'])->name('products.balance.update');
    Route::resource('products', ProductController::class);
    Route::post('/business-site-operations/{businessSiteOperation}/closure/preview', [OperationClosureCorrectionController::class, 'preview'])->name('business-site-operations.closure-preview');
    Route::post('/business-site-operations/{businessSiteOperation}/closure', [OperationClosureCorrectionController::class, 'store'])->name('business-site-operations.closure-store');
    Route::get('/business-site-operations/{businessSiteOperation}', [BusinessSiteOperationController::class, 'show'])->name('business-site-operations.show');
    Route::delete('/business-site-operations/{businessSiteOperation}', [BusinessSiteOperationController::class, 'destroy'])->name('business-site-operations.destroy');
    Route::patch('/business-sites/{businessSite}/start', [BusinessSiteController::class, 'start'])->name('business-sites.start');
    Route::patch('/business-sites/{businessSite}/stop', [BusinessSiteController::class, 'stop'])->name('business-sites.stop');
    Route::get('/business-sites/statistics', [BusinessSiteReportController::class, 'statistics'])->name('business-sites.statistics');
    Route::get('/business-sites/summaries', [BusinessSiteReportController::class, 'directory'])->name('business-sites.summaries');
    Route::get('/business-sites/{businessSite}/summary', [BusinessSiteReportController::class, 'summary'])->name('business-sites.summary');
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
