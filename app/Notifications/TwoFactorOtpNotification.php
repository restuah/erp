<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TwoFactorOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string  $otp  6-digit OTP code
     * @param  string  $userName  Name of the user
     * @param  string  $action  'activation' | 'login'
     */
    public function __construct(
        public string $otp,
        public string $userName,
        public string $action = 'activation'
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

        if ($this->action === 'login') {
            $subject = "[{$appName}] Kode OTP Verifikasi Masuk (2FA)";
            $intro = "Kami mendeteksi percobaan masuk ke akun ERP Anda ({$notifiable->email}).";
            $instruction = 'Gunakan kode OTP berikut untuk menyelesaikan proses masuk ke akun Anda:';
        } else {
            $subject = "[{$appName}] Kode OTP Verifikasi Aktivasi 2FA";
            $intro = "Kami menerima permintaan untuk mengaktifkan Autentikasi Dua Faktor (2FA) pada akun ERP Anda ({$notifiable->email}).";
            $instruction = 'Gunakan kode OTP berikut untuk memvalidasi email Anda dan mengaktifkan 2FA:';
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Halo, {$this->userName}!")
            ->line($intro)
            ->line($instruction)
            ->line(new HtmlString(
                '<div style="text-align: center; margin: 28px 0;">'.
                '<span style="display: inline-block; font-size: 34px; font-weight: 800; letter-spacing: 10px; color: #4338ca; background-color: #eef2ff; padding: 14px 28px; border-radius: 12px; border: 2px dashed #6366f1; font-family: monospace;">'.
                $this->otp.
                '</span>'.
                '</div>'
            ))
            ->line('Perhatian: Kode OTP ini hanya berlaku selama 15 menit. Demi keamanan akun, JANGAN PERNAH membagikan kode ini kepada siapa pun termasuk staf IT.')
            ->line('Jika Anda tidak merasa melakukan tindakan ini, segera amankan akun Anda atau hubungi administrator sistem.')
            ->salutation("Salam hangat,\nTim IT {$appName}");
    }
}
