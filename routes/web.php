<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TwoFactorAuthenticationController;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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

    // Access Management Modules
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

    // Recycle Bin Module
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/{type}/{id}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::delete('/recycle-bin/{type}/{id}/force-delete', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');
    Route::post('/recycle-bin/{type}/restore-all', [RecycleBinController::class, 'restoreAll'])->name('recycle-bin.restore-all');
    Route::delete('/recycle-bin/{type}/empty', [RecycleBinController::class, 'empty'])->name('recycle-bin.empty');

    // Activity Logs Module (Audit Trail)
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/export/csv', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::delete('/activity-logs/clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');

    // Master Data
    Route::prefix('master')->group(function () {
        // Kalender
        Route::name('calendar.')->group(function () {
            Route::get('/calendar', [CalendarController::class, 'index'])->name('index');
            Route::post('/calendar/sync', [CalendarController::class, 'sync'])->name('sync');
            Route::patch('/calendar/{calendarDay}', [CalendarController::class, 'update'])->name('update');
            Route::post('/calendar/reset', [CalendarController::class, 'reset'])->name('reset');
        });

        // Mata Uang (Currencies)
        Route::resource('currencies', CurrencyController::class)->except(['create', 'show', 'edit']);
    });
});

require __DIR__.'/auth.php';
