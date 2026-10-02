<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class SendSmsAction
{
    public function execute(string $userId, SmsService $smsService): array
    {
        $key = "sms-send:" . Auth::id();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return [
                'message' => 'درخواست‌های شما بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید.',
                'type' => 'error',
            ];
        }

        $user = User::visibleOnBoard()->find($userId);

        if (!$user) {
            return [
                'message' => 'کاربر یافت نشد.',
                'type' => 'error',
            ];
        }

        if ($user->sms_number) {
            RateLimiter::hit($key, 60);
            $smsService->send($user->sms_number, " to be inserted later on ...");
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
