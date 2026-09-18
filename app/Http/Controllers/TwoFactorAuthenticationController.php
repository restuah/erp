<?php

namespace App\Http\Controllers;

use App\Notifications\TwoFactorOtpNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Request activation of Two-Factor Authentication (sends OTP to email).
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return Redirect::route('profile.edit')->with('info', 'Autentikasi Dua Faktor (2FA) sudah aktif.');
        }

        $cacheKey = 'two_factor_activation_'.$user->id;
        $otp = (string) random_int(100000, 999999);

        Cache::put($cacheKey, [
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], now()->addMinutes(15));

        if (app()->environment('local')) {
            Log::info("2FA Activation OTP for {$user->email}: {$otp}");
        }

        $user->notify(new TwoFactorOtpNotification($otp, $user->name, 'activation'));

        ActivityLogger::log(
            description: "Permintaan pengaktifan 2FA diajukan oleh {$user->name}, kode OTP dikirim ke email",
            event: 'two_factor_activation_requested',
            logName: 'auth',
            subject: $user,
            properties: [
                'email' => $user->email,
            ],
            status: 'info',
            causer: $user
        );

        return Redirect::route('profile.edit')
            ->with('status', 'two-factor-otp-sent')
            ->with('info', "Kode OTP aktivasi telah dikirimkan ke {$user->email}. Silakan masukkan kode untuk menyelesaikan aktivasi.");
    }

    /**
     * Verify OTP and complete 2FA activation.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $cacheKey = 'two_factor_activation_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if (! $pendingData) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak ditemukan atau telah kedaluwarsa. Silakan ajukan aktivasi kembali.',
            ]);
        }

        if (($pendingData['attempts'] ?? 0) >= 5) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'otp' => 'Batas percobaan salah terlampaui. Kode OTP dibatalkan demi keamanan. Silakan ajukan ulang.',
            ]);
        }

        if (! Hash::check($request->otp, $pendingData['otp'])) {
            $pendingData['attempts'] = ($pendingData['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $pendingData, now()->addMinutes(15));

            $remaining = 5 - $pendingData['attempts'];
            throw ValidationException::withMessages([
                'otp' => "Kode OTP yang Anda masukkan salah. Sisa kesempatan: {$remaining} kali.",
            ]);
        }

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        Cache::forget($cacheKey);

        ActivityLogger::log(
            description: "Autentikasi Dua Faktor (2FA) akun {$user->name} berhasil diaktifkan",
            event: 'two_factor_enabled',
            logName: 'auth',
            subject: $user,
            properties: [
                'email' => $user->email,
                'confirmed_at' => $user->two_factor_confirmed_at->toIso8601String(),
            ],
            status: 'success',
            causer: $user
        );

        return Redirect::route('profile.edit')->with('success', 'Autentikasi Dua Faktor (2FA) berhasil diaktifkan.');
    }

    /**
     * Resend OTP for 2FA activation.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cacheKey = 'two_factor_activation_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if (! $pendingData) {
            return Redirect::route('profile.edit')->with('error', 'Tidak ada proses pengaktifan 2FA yang sedang berjalan.');
        }

        $secondsSinceLastSent = now()->timestamp - ($pendingData['sent_at'] ?? 0);
        if ($secondsSinceLastSent < 60) {
            $wait = 60 - $secondsSinceLastSent;

            return Redirect::route('profile.edit')->with('error', "Harap tunggu {$wait} detik sebelum meminta kode OTP baru.");
        }

        $otp = (string) random_int(100000, 999999);
        $pendingData['otp'] = Hash::make($otp);
        $pendingData['attempts'] = 0;
        $pendingData['sent_at'] = now()->timestamp;
        Cache::put($cacheKey, $pendingData, now()->addMinutes(15));

        if (app()->environment('local')) {
            Log::info("2FA Activation OTP (resend) for {$user->email}: {$otp}");
        }

        $user->notify(new TwoFactorOtpNotification($otp, $user->name, 'activation'));

        return Redirect::route('profile.edit')->with('success', "Kode OTP baru telah dikirimkan ke {$user->email}.");
    }

    /**
     * Cancel pending 2FA activation.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cacheKey = 'two_factor_activation_'.$user->id;

        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);

            ActivityLogger::log(
                description: "Proses pengaktifan 2FA akun {$user->name} dibatalkan",
                event: 'two_factor_activation_cancelled',
                logName: 'auth',
                subject: $user,
                properties: [
                    'email' => $user->email,
                ],
                status: 'info',
                causer: $user
            );
        }

        return Redirect::route('profile.edit')->with('info', 'Proses pengaktifan 2FA dibatalkan.');
    }

    /**
     * Disable Two-Factor Authentication.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->save();

        Cache::forget('two_factor_activation_'.$user->id);

        ActivityLogger::log(
            description: "Autentikasi Dua Faktor (2FA) akun {$user->name} dinonaktifkan",
            event: 'two_factor_disabled',
            logName: 'auth',
            subject: $user,
            properties: [
                'email' => $user->email,
            ],
            status: 'warning',
            causer: $user
        );

        return Redirect::route('profile.edit')->with('success', 'Autentikasi Dua Faktor (2FA) berhasil dinonaktifkan.');
    }
}
