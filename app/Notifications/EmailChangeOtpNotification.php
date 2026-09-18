<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class EmailChangeOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $otp,
        public string $userName,
        public string $newEmail
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'ERP System');

        return (new MailMessage)
            ->subject("[{$appName}] Kode OTP Verifikasi Perubahan Email")
            ->greeting("Halo, {$this->userName}!")
            ->line("Kami menerima permintaan untuk mengubah alamat email akun ERP Anda ke alamat ini ({$this->newEmail}).")
            ->line('Silakan gunakan kode OTP di bawah ini untuk memverifikasi dan menyelesaikan pembaruan email Anda:')
            ->line(new HtmlString(
                '<div style="text-align: center; margin: 28px 0;">'.
                '<span style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #4338ca; background-color: #eef2ff; padding: 14px 28px; border-radius: 10px; border: 2px dashed #6366f1; font-family: monospace;">'.
                $this->otp.
                '</span>'.
                '</div>'
            ))
            ->line('Perhatian: Kode OTP ini berlaku selama 15 menit. Demi keamanan akun, jangan pernah membagikan kode ini kepada siapa pun.')
            ->line('Jika Anda tidak meminta perubahan alamat email, abaikan email ini. Alamat email akun Anda tidak akan berubah.')
            ->salutation("Salam hangat,\nTim IT {$appName}");
    }
}
