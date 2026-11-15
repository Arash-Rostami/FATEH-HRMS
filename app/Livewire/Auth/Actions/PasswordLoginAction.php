<?php

namespace App\Livewire\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordLoginAction
{
    public function execute(string $email, string $password, bool $remember, string $ip): ?string
    {
        $key = 'login:' . Str::lower($email) . '|' . $ip;

        if (RateLimiter::tooManyAttempts($key, 4)) {
            return 'تعداد تلاش‌های ورود بیش از حد مجاز است. لطفاً ' . RateLimiter::availableIn($key) . ' ثانیه دیگر تلاش کنید.';
        }

        $inactive = false;

        $loggedIn = Auth::attemptWhen(
            ['email' => $email, 'password' => $password],
            function ($user) use (&$inactive): bool {
                $inactive = ! $user->isActive();

                return ! $inactive;
            },
            $remember
        );

        if ($inactive) {
            return 'حساب کاربری شما غیرفعال است.';
        }

        if (! $loggedIn) {
            RateLimiter::hit($key);

            return 'اطلاعات ورود اشتباه است یا حساب کاربری وجود ندارد.';
        }

        RateLimiter::clear($key);

        return null;
    }
}
