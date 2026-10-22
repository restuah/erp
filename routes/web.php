<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetClassificationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TermOfPaymentController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // User Profile & 2FA
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/email/verify-otp', [ProfileController::class, 'verifyEmailOtp'])->name('profile.email.verify');
    Route::post('/profile/email/resend-otp', [ProfileController::class, 'resendEmailOtp'])->name('profile.email.resend');
    Route::delete('/profile/email/cancel-otp', [ProfileController::class, 'cancelEmailOtp'])->name('profile.email.cancel');

    // Two-Factor Authentication (2FA) in Profile
    Route::post('/profile/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable'])->name('two-factor.enable');
    Route::post('/profile/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/profile/two-factor/resend', [TwoFactorAuthenticationController::class, 'resend'])->name('two-factor.resend');
    Route::delete('/profile/two-factor/cancel', [TwoFactorAuthenticationController::class, 'cancel'])->name('two-factor.cancel');
    Route::delete('/profile/two-factor', [TwoFactorAuthenticationController::class, 'disable'])->name('two-factor.disable');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // -------------------------------------------------------------------------
    // ACCESS MANAGEMENT & GOVERNANCE MODULES
    // -------------------------------------------------------------------------

    // Users
    Route::middleware('permission:users.view')->get('users', [UserController::class, 'index'])->name('users.index');
    Route::middleware('permission:users.create')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('permission:users.edit')->group(function () {
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
    });
    Route::middleware('permission:users.delete')->delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Roles
    Route::middleware('permission:roles.view')->get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware('permission:roles.create')->group(function () {
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('permission:roles.edit')->group(function () {
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('permission:roles.delete')->delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Permissions
    Route::middleware('permission:permissions.view')->get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::middleware('permission:permissions.create')->group(function () {
        Route::get('permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
    });
    Route::middleware('permission:permissions.edit')->group(function () {
        Route::get('permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::match(['put', 'patch'], 'permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    });
    Route::middleware('permission:permissions.delete')->delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // Recycle Bin Module (Superadmin exclusive)
    Route::middleware('role:Superadmin')->group(function () {
        Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
        Route::post('/recycle-bin/{type}/{id}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
        Route::delete('/recycle-bin/{type}/{id}/force-delete', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');
        Route::post('/recycle-bin/{type}/restore-all', [RecycleBinController::class, 'restoreAll'])->name('recycle-bin.restore-all');
        Route::delete('/recycle-bin/{type}/empty', [RecycleBinController::class, 'empty'])->name('recycle-bin.empty');
    });

    // Activity Logs Module (Audit Trail)
    Route::middleware('permission:activity_logs.view')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/export/csv', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    });
    Route::middleware('permission:activity_logs.delete')->delete('/activity-logs/clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');

    // -------------------------------------------------------------------------
    // DATA INDUK (MASTER DATA) MODULES
    // -------------------------------------------------------------------------
    Route::prefix('master')->group(function () {
        // Kalender
        Route::name('calendar.')->group(function () {
            Route::middleware('permission:calendar.view')->get('/calendar', [CalendarController::class, 'index'])->name('index');
            Route::middleware('permission:calendar.sync')->post('/calendar/sync', [CalendarController::class, 'sync'])->name('sync');
            Route::middleware('permission:calendar.edit')->patch('/calendar/{calendarDay}', [CalendarController::class, 'update'])->name('update');
            Route::middleware('permission:calendar.edit')->post('/calendar/reset', [CalendarController::class, 'reset'])->name('reset');
        });

        // Mata Uang (Currencies)
        Route::middleware('permission:currencies.view')->get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::middleware('permission:currencies.create')->post('currencies', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::middleware('permission:currencies.edit')->match(['put', 'patch'], 'currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::middleware('permission:currencies.delete')->delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

        // Kurs Nilai Tukar (Exchange Rates)
        Route::middleware('permission:exchange_rates.view')->get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
        Route::middleware('permission:exchange_rates.sync')->post('exchange-rates/sync', [ExchangeRateController::class, 'sync'])->name('exchange-rates.sync');
        Route::middleware('permission:exchange_rates.create')->post('exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
        Route::middleware('permission:exchange_rates.edit')->match(['put', 'patch'], 'exchange-rates/{exchange_rate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
        Route::middleware('permission:exchange_rates.delete')->delete('exchange-rates/{exchange_rate}', [ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy');

        // Master Budget
        Route::middleware('permission:budgets.view')->group(function () {
            Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
            Route::get('budgets/generate-code', [BudgetController::class, 'generateCode'])->name('budgets.generate-code');
        });
        Route::middleware('permission:budgets.create')->post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::middleware('permission:budgets.edit')->match(['put', 'patch'], 'budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::middleware('permission:budgets.delete')->delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        // Master Klasifikasi Budget
        Route::middleware('permission:budget_classifications.view')->get('budget-classifications', [BudgetClassificationController::class, 'index'])->name('budget-classifications.index');
        Route::middleware('permission:budget_classifications.create')->post('budget-classifications', [BudgetClassificationController::class, 'store'])->name('budget-classifications.store');
        Route::middleware('permission:budget_classifications.edit')->match(['put', 'patch'], 'budget-classifications/{budget_classification}', [BudgetClassificationController::class, 'update'])->name('budget-classifications.update');
        Route::middleware('permission:budget_classifications.delete')->delete('budget-classifications/{budget_classification}', [BudgetClassificationController::class, 'destroy'])->name('budget-classifications.destroy');

        // Master Bagan Akun (Chart of Accounts)
        Route::middleware('permission:coa.view')->get('chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart-of-accounts.index');
        Route::middleware('permission:coa.create')->post('chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('chart-of-accounts.store');
        Route::middleware('permission:coa.edit')->match(['put', 'patch'], 'chart-of-accounts/{chart_of_account}', [ChartOfAccountController::class, 'update'])->name('chart-of-accounts.update');
        Route::middleware('permission:coa.delete')->delete('chart-of-accounts/{chart_of_account}', [ChartOfAccountController::class, 'destroy'])->name('chart-of-accounts.destroy');

        // Master Satuan (Unit of Measures)
        Route::middleware('permission:uom.view')->get('unit-of-measures', [UnitOfMeasureController::class, 'index'])->name('unit-of-measures.index');
        Route::middleware('permission:uom.create')->post('unit-of-measures', [UnitOfMeasureController::class, 'store'])->name('unit-of-measures.store');
        Route::middleware('permission:uom.edit')->match(['put', 'patch'], 'unit-of-measures/{unit_of_measure}', [UnitOfMeasureController::class, 'update'])->name('unit-of-measures.update');
        Route::middleware('permission:uom.delete')->delete('unit-of-measures/{unit_of_measure}', [UnitOfMeasureController::class, 'destroy'])->name('unit-of-measures.destroy');

        // Master Term of Payment (TOP)
        Route::middleware('permission:term_of_payments.view')->get('term-of-payments', [TermOfPaymentController::class, 'index'])->name('term-of-payments.index');
        Route::middleware('permission:term_of_payments.create')->post('term-of-payments', [TermOfPaymentController::class, 'store'])->name('term-of-payments.store');
        Route::middleware('permission:term_of_payments.edit')->match(['put', 'patch'], 'term-of-payments/{term_of_payment}', [TermOfPaymentController::class, 'update'])->name('term-of-payments.update');
        Route::middleware('permission:term_of_payments.delete')->delete('term-of-payments/{term_of_payment}', [TermOfPaymentController::class, 'destroy'])->name('term-of-payments.destroy');

        // Master Supplier (Direct & Indirect)
        Route::middleware('permission:suppliers.view')->group(function () {
            Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('suppliers/generate-code', [SupplierController::class, 'generateCode'])->name('suppliers.generate-code');
        });
        Route::middleware('permission:suppliers.create')->post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::middleware('permission:suppliers.edit')->match(['put', 'patch'], 'suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::middleware('permission:suppliers.delete')->delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });
});

require __DIR__.'/auth.php';
