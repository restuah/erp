<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Notifications\TwoFactorOtpNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        // Check if user has 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            $otp = (string) random_int(100000, 999999);
            $cacheKey = 'two_factor_login_'.$user->id;

            Cache::put($cacheKey, [
                'otp' => Hash::make($otp),
                'attempts' => 0,
                'sent_at' => now()->timestamp,
            ], now()->addMinutes(15));

            if (app()->environment('local')) {
                Log::info("2FA Login OTP for {$user->email}: {$otp}");
            }

            $user->notify(new TwoFactorOtpNotification($otp, $user->name, 'login'));

            $request->session()->put('login.two_factor_user_id', $user->id);
            $request->session()->put('login.two_factor_remember', $request->boolean('remember'));

            ActivityLogger::log(
                description: "Tantangan 2FA dikirimkan ke email untuk login akun {$user->name}",
                event: 'two_factor_login_challenge',
                logName: 'auth',
                subject: $user,
                properties: ['email' => $user->email],
                status: 'info'
            );

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        ActivityLogger::log(
            description: "Pengguna {$user->name} berhasil masuk ke sistem",
            event: 'login',
            logName: 'auth',
            subject: $user,
            status: 'success',
            causer: $user
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
