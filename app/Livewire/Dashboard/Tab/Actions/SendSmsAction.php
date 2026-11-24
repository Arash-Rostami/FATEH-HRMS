<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\NotifyLog;
use App\Models\User;
use App\Services\Notify\Channels\SmsChannel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SendSmsAction
{
    public const DEFAULT_TEXT = 'لطفاً با من تماس بگیرید.';

    public function execute(string $userId, SmsChannel $sms, ?string $text = null, string $source = NotifyLog::SOURCE_STATUS, ?string $occasionKey = null): array
    {
        if (tenantOff('notify:sms')) {
            return ['message' => 'ارسال پیامک در این سامانه فعال نیست.', 'type' => 'error'];
        }

        if ($occasionKey && NotifyLog::sentOccasionKeys((int) Auth::id(), [$occasionKey]) !== []) {
            return ['message' => 'پیامک این مناسبت پیش‌تر ارسال شده است.', 'type' => 'error'];
        }

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
            if ($occasionKey && !Cache::add("sms-occasion:" . Auth::id() . ":{$occasionKey}", 1, 60)) {
                return ['message' => 'پیامک این مناسبت پیش‌تر ارسال شده است.', 'type' => 'error'];
            }

            RateLimiter::hit($key, 60);
            RateLimiter::hit($perRecipient, 3600);

            $name = Auth::user()?->name;
            $body = ($name ? $name . ': ' : '') . (filled($text) ? mb_substr(trim($text), 0, 140) : self::DEFAULT_TEXT);

            $delivered = $sms->text($user->sms_number, $body);
            NotifyLog::audit($source, (int) Auth::id(), $user->id, $user->sms_number, $delivered, null, $occasionKey);

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
