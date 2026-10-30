<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\NotifyLog;
use App\Models\User;
use App\Services\Notify\Channels\SmsChannel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class SendSmsAction
{
    public function execute(string $userId, SmsChannel $sms, ?string $text = null): array
    {
        $key = "sms-send:" . Auth::id();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return [
                'message' => 'درخواست‌های شما بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید.',
                'type' => 'error',
            ];
        }

        $user = User::visibleOnBoard()->with('profile:id,user_id,cellphone')->find($userId);

        if (!$user) {
            return [
                'message' => 'کاربر یافت نشد.',
                'type' => 'error',
            ];
        }

        if (presence($user->presence)->isObscured()) {
            return [
                'message' => 'این همکار در حال حاضر پیامک دریافت نمی‌کند.',
                'type' => 'error',
            ];
        }

        if (!$sms->isConfigured()) {
            return [
                'message' => 'سرویس پیامک هنوز پیکربندی نشده است؛ با مدیر سیستم تماس بگیرید.',
                'type' => 'error',
            ];
        }

        $perRecipient = "sms-to:{$user->id}:" . Auth::id();

        if (RateLimiter::tooManyAttempts($perRecipient, 3)) {
            return [
                'message' => 'به این همکار اخیراً چند پیامک فرستاده شده است؛ بعداً دوباره تلاش کنید.',
                'type' => 'error',
            ];
        }

        if ($user->sms_number) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($perRecipient, 3600);

            $name = Auth::user()?->name;
            $body = ($name ? $name . ': ' : '') . (filled($text) ? mb_substr(trim($text), 0, 140) : 'لطفاً با من تماس بگیرید.');

            $delivered = $sms->text($user->sms_number, $body);
            NotifyLog::audit(NotifyLog::SOURCE_STATUS, (int) Auth::id(), $user->id, $user->sms_number, $delivered);

            if (!$delivered) {
                return [
                    'message' => 'ارسال پیامک ناموفق بود؛ کمی بعد دوباره تلاش کنید.',
                    'type' => 'error',
                ];
            }

            return [
                'message' => 'پیامک با موفقیت ارسال شد',
                'type' => 'success',
            ];
        }

        return [
            'message' => 'شماره تلفن همراه یافت نشد',
            'type' => 'error',
        ];
    }
}
