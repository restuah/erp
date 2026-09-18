<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * Create a notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 30);
        $appName = config('app.name', 'ERP System');

        return (new MailMessage)
            ->subject("[{$appName}] Permintaan Reset Kata Sandi")
            ->greeting("Halo, {$notifiable->name}!")
            ->line('Anda menerima email ini karena kami menerima permintaan untuk mengatur ulang kata sandi akun ERP Anda.')
            ->action('Atur Ulang Kata Sandi', $url)
            ->line("Perhatian: Tautan reset kata sandi di atas hanya berlaku selama {$expire} menit.")
            ->line('Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini. Kata sandi akun Anda tetap aman dan tidak akan berubah.')
            ->salutation("Salam hangat,\nTim IT {$appName}");
    }
}
