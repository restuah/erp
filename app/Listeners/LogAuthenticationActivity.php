<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class LogAuthenticationActivity
{
    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        ActivityLogger::log(
            description: "Pengguna {$user->name} berhasil masuk ke sistem",
            event: 'login',
            logName: 'auth',
            subject: $user,
            properties: [
                'email' => $user->email,
                'name' => $user->name,
            ],
            status: 'success',
            causer: $user
        );
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        /** @var User|null $user */
        $user = $event->user;

        if ($user) {
            ActivityLogger::log(
                description: "Pengguna {$user->name} keluar dari sistem",
                event: 'logout',
                logName: 'auth',
                subject: $user,
                properties: [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                status: 'info',
                causer: $user
            );
        }
    }

    /**
     * Handle failed login event.
     */
    public function handleFailed(Failed $event): void
    {
        $attemptedEmail = $event->credentials['email'] ?? 'tidak diketahui';

        ActivityLogger::log(
            description: "Percobaan masuk gagal untuk akun: {$attemptedEmail}",
            event: 'failed_login',
            logName: 'auth',
            subject: $event->user,
            properties: [
                'attempted_email' => $attemptedEmail,
            ],
            status: 'danger',
            causer: $event->user
        );
    }

    /**
     * Handle password reset event.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        /** @var User $user */
        $user = $event->user;

        ActivityLogger::log(
            description: "Kata sandi akun {$user->name} ({$user->email}) berhasil diatur ulang",
            event: 'password_reset',
            logName: 'auth',
            subject: $user,
            properties: [
                'email' => $user->email,
            ],
            status: 'warning',
            causer: $user
        );
    }

    /**
     * Register listeners for subscriber.
     */
    public function subscribe(): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
