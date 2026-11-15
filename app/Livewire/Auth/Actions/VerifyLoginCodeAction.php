<?php

namespace App\Livewire\Auth\Actions;

use App\Models\User;
use App\Services\Notify\Channels\SmsChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class VerifyLoginCodeAction
{
    public function execute(string $phone, string $code, string $ip, SmsChannel $sms): ?User
    {
        if (! SendLoginCodeAction::available()) {
            return null;
        }

        $tail = substr($sms->normalize($phone), -10);

        if (RateLimiter::hit("login-otp-try-ip:{$ip}", 600) > 60) {
            return null;
        }

        $entry = Cache::get("login-otp:{$tail}");

        if (! $entry || RateLimiter::hit("login-otp-try:{$tail}", 120) > 5) {
            Cache::forget("login-otp:{$tail}");

            return null;
        }

        if (! hash_equals($entry['hash'], hash_hmac('sha256', $code, config('app.key')))) {
            return null;
        }

        Cache::forget("login-otp:{$tail}");

        $user = User::find($entry['user']);

        return $user?->isActive() ? $user : null;
    }
}
