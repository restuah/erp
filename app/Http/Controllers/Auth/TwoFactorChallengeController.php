<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TwoFactorOtpNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the 2FA login challenge view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('login.two_factor_user_id');

        if (! $userId || ! ($user = User::find($userId))) {
            return redirect()->route('login');
        }

        $cacheKey = 'two_factor_login_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        return Inertia::render('Auth/TwoFactorChallenge', [
            'email' => $this->maskEmail($user->email),
            'cooldown' => $pendingData ? max(0, 60 - (now()->timestamp - ($pendingData['sent_at'] ?? 0))) : 0,
        ]);
    }

    /**
     * Verify the 2FA login challenge OTP and authenticate user.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.two_factor_user_id');

        if (! $userId || ! ($user = User::find($userId))) {
            return redirect()->route('login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $cacheKey = 'two_factor_login_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if (! $pendingData) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak ditemukan atau telah kedaluwarsa. Silakan minta kode baru.',
            ]);
        }

        if (($pendingData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            $request->session()->forget(['login.two_factor_user_id', 'login.two_factor_remember']);

            throw ValidationException::withMessages([
                'otp' => 'Batas percobaan salah terlampaui. Sesi masuk dibatalkan demi keamanan. Silakan coba masuk kembali.',
            ]);
        }

        if (! Hash::check($request->otp, $pendingData['otp'])) {
            $pendingData['attempts'] = ($pendingData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $pendingData, now()->addMinutes(15));

            $remaining = 5 - $pendingData['attempts'];

            ActivityLogger::log(
                description: "Percobaan kode OTP 2FA salah saat login untuk akun {$user->name}",
                event: 'two_factor_login_failed',
                logName: 'auth',
                subject: $user,
                properties: ['remaining_attempts' => $remaining],
                status: 'danger'
            );

            throw ValidationException::withMessages([
                'otp' => "Kode OTP yang Anda masukkan salah. Sisa kesempatan: {$remaining} kali.",
            ]);
        }

        // OTP is valid!
        Cache::forget($cacheKey);
        $remember = (bool) $request->session()->pull('login.two_factor_remember', false);
        $request->session()->forget('login.two_factor_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        ActivityLogger::log(
            description: "Pengguna {$user->name} berhasil masuk ke sistem dengan verifikasi 2FA",
            event: 'login',
            logName: 'auth',
            subject: $user,
            status: 'success',
            causer: $user
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Resend 2FA login challenge OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.two_factor_user_id');

        if (! $userId || ! ($user = User::find($userId))) {
            return redirect()->route('login');
        }

        $cacheKey = 'two_factor_login_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if ($pendingData) {
            $secondsSinceLastSent = now()->timestamp - ($pendingData['sent_at'] ?? 0);
            if ($secondsSinceLastSent < 60) {
                $wait = 60 - $secondsSinceLastSent;

                return back()->with('error', "Harap tunggu {$wait} detik sebelum meminta kode OTP baru.");
            }
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put($cacheKey, [
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], now()->addMinutes(15));

        if (app()->environment('local')) {
            Log::info("2FA Login OTP (resend) for {$user->email}: {$otp}");
        }

        $user->notify(new TwoFactorOtpNotification($otp, $user->name, 'login'));

        return back()->with('success', 'Kode OTP baru telah dikirimkan ke email Anda.');
    }

    /**
     * Cancel the 2FA login challenge and return to login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.two_factor_user_id');

        if ($userId) {
            Cache::forget('two_factor_login_'.$userId);
        }

        $request->session()->forget(['login.two_factor_user_id', 'login.two_factor_remember']);

        return redirect()->route('login');
    }

    /**
     * Mask email address for privacy (e.g. j***e@example.com).
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1).'*';
        } else {
            $maskedName = substr($name, 0, 2).str_repeat('*', max(1, $len - 3)).substr($name, -1);
        }

        return $maskedName.'@'.$domain;
    }
}
