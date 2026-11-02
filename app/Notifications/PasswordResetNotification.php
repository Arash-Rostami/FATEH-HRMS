<?php

namespace App\Notifications;

use App\Mail\NotifyMail;
use App\Services\Notify\Message;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    public ?string $url = null;

    public function __construct(private readonly string $token)
    {
    }

    public function via($notifiable): array
    {
        return blank($notifiable->email) ? [] : ['mail'];
    }

    public function toMail($notifiable): NotifyMail
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        $message = new Message(
            'بازیابی رمز عبور',
            [
                'درخواست بازیابی رمز عبور حساب شما در '.config('app.name').' ثبت شد.',
                'برای انتخاب رمز جدید، روی دکمهٔ زیر بزنید. این پیوند تا '.$minutes.' دقیقه معتبر است.',
                'اگر این درخواست از طرف شما نبوده است، نیازی به هیچ اقدامی نیست و رمز فعلی شما تغییری نمی‌کند.',
            ],
            'password-reset',
            'حساب کاربری',
            'امنیت',
            'styled',
            $this->url ?? url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false)),
            'تغییر رمز عبور',
        );

        return (new NotifyMail($message))->to($notifiable->email);
    }
}
