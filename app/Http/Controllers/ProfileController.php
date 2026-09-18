<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\EmailChangeOtpNotification;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $pendingData = Cache::get('email_otp_'.$user->id);
        $pending2FaData = Cache::get('two_factor_activation_'.$user->id);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'pendingEmail' => $pendingData['email'] ?? null,
            'otpCooldown' => $pendingData ? max(0, 60 - (now()->timestamp - ($pendingData['sent_at'] ?? 0))) : 0,
            'twoFactorEnabled' => (bool) $user->two_factor_enabled,
            'twoFactorConfirmedAt' => $user->two_factor_confirmed_at?->format('d M Y, H:i'),
            'pendingTwoFactor' => (bool) $pending2FaData,
            'twoFactorCooldown' => $pending2FaData ? max(0, 60 - (now()->timestamp - ($pending2FaData['sent_at'] ?? 0))) : 0,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $newEmail = strtolower(trim($validated['email']));
        $isEmailChanged = ($newEmail !== strtolower(trim($user->email)));

        // Always update name
        $user->name = $validated['name'];

        // Handle avatar removal
        if ($request->boolean('remove_avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // If email was not changed, save directly
        if (! $isEmailChanged) {
            $user->save();

            return Redirect::route('profile.edit')->with('success', 'Profil dan avatar berhasil diperbarui.');
        }

        // Email IS changed: Save name and avatar changes on user first (keep old email)
        $user->save();

        $cacheKey = 'email_otp_'.$user->id;
        $otp = (string) random_int(100000, 999999);

        Cache::put($cacheKey, [
            'email' => $newEmail,
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], now()->addMinutes(15));

        if (app()->environment('local')) {
            Log::info("Email change OTP for {$newEmail}: {$otp}");
        }

        // Send OTP notification to NEW email
        Notification::route('mail', $newEmail)->notify(
            new EmailChangeOtpNotification($otp, $user->name, $newEmail)
        );

        ActivityLogger::log(
            description: "Permintaan perubahan email ke {$newEmail} diajukan oleh {$user->name}",
            event: 'email_change_requested',
            logName: 'auth',
            subject: $user,
            properties: [
                'current_email' => $user->email,
                'requested_email' => $newEmail,
            ],
            status: 'info',
            causer: $user
        );

        return Redirect::route('profile.edit')
            ->with('status', 'otp-sent')
            ->with('info', "Kode OTP telah dikirimkan ke {$newEmail}. Silakan masukkan kode untuk menyelesaikan perubahan email.");
    }

    /**
     * Verify OTP and update user's email.
     */
    public function verifyEmailOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $cacheKey = 'email_otp_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if (! $pendingData) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak ditemukan atau telah kedaluwarsa. Silakan ajukan perubahan email kembali.',
            ]);
        }

        // Check attempts limit (max 5)
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

        $newEmail = $pendingData['email'];

        // Double check uniqueness in case another user took it while pending
        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'otp' => 'Alamat email tersebut sudah digunakan oleh akun lain.',
            ]);
        }

        $oldEmail = $user->email;
        $user->email = $newEmail;
        $user->email_verified_at = now();
        $user->save();

        Cache::forget($cacheKey);

        ActivityLogger::log(
            description: "Alamat email akun {$user->name} berhasil diubah dari {$oldEmail} ke {$newEmail} dan terverifikasi",
            event: 'email_change_verified',
            logName: 'auth',
            subject: $user,
            properties: [
                'old_email' => $oldEmail,
                'new_email' => $newEmail,
            ],
            status: 'success',
            causer: $user
        );

        return Redirect::route('profile.edit')->with('success', 'Alamat email baru berhasil disimpan dan diverifikasi.');
    }

    /**
     * Resend OTP to pending email.
     */
    public function resendEmailOtp(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cacheKey = 'email_otp_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if (! $pendingData) {
            return Redirect::route('profile.edit')->with('error', 'Tidak ada permintaan perubahan email yang sedang berlangsung.');
        }

        // Throttle resend (cooldown 60s)
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
            Log::info("Email change OTP (resend) for {$pendingData['email']}: {$otp}");
        }

        Notification::route('mail', $pendingData['email'])->notify(
            new EmailChangeOtpNotification($otp, $user->name, $pendingData['email'])
        );

        return Redirect::route('profile.edit')->with('success', "Kode OTP baru telah dikirimkan ke {$pendingData['email']}.");
    }

    /**
     * Cancel pending email change.
     */
    public function cancelEmailOtp(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cacheKey = 'email_otp_'.$user->id;
        $pendingData = Cache::get($cacheKey);

        if ($pendingData) {
            Cache::forget($cacheKey);

            ActivityLogger::log(
                description: "Permintaan perubahan email ke {$pendingData['email']} dibatalkan",
                event: 'email_change_cancelled',
                logName: 'auth',
                subject: $user,
                properties: [
                    'cancelled_email' => $pendingData['email'],
                ],
                status: 'info',
                causer: $user
            );
        }

        return Redirect::route('profile.edit')->with('info', 'Permintaan perubahan email telah dibatalkan.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Akun Anda berhasil dihapus.');
    }
}
