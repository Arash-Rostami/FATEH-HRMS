<?php

namespace App\Livewire\Auth\Actions;

use App\Models\User;
use App\Services\Notify\Channels\SmsChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SendLoginCodeAction
{
    public const COOLDOWN = 60;

    public static function available(): bool
    {
        return ! tenantOff('notify:sms') && app(SmsChannel::class)->isConfigured();
    }

    public function execute(string $phone, string $ip, SmsChannel $sms): ?string
    {
        if (! self::available()) {
            return 'ورود با کد پیامکی در دسترس نیست.';
        }

        if (RateLimiter::tooManyAttempts($ipKey = "login-otp-ip:{$ip}", 60)) {
            return 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.';
        }

        RateLimiter::hit($ipKey, 3600);

        $to = $sms->normalize($phone);
        $tail = substr($to, -10);

        $users = User::active()
            ->whereHas('profile', fn ($query) => $query->where('cellphone', 'like', '%' . $tail))
            ->limit(2)
            ->pluck('id');

        if ($users->count() !== 1) {
            return 'امکان ارسال کد ورود به این شماره وجود ندارد. شمارهٔ موبایل ثبت‌شده در پروفایل خود را وارد کنید یا با رمز عبور وارد شوید.';
        }

        if (RateLimiter::tooManyAttempts($coolKey = "login-otp-cool:{$tail}", 1)) {
            return 'کد را به‌تازگی دریافت کرده‌اید. ' . RateLimiter::availableIn($coolKey) . ' ثانیه دیگر دوباره تلاش کنید.';
        }

        if (RateLimiter::tooManyAttempts($hourKey = "login-otp-hour:{$tail}", 5)) {
            return 'سقف ۵ بار درخواست کد برای این شماره پر شده است. یک ساعت بعد دوباره تلاش کنید.';
        }

        if (RateLimiter::tooManyAttempts($dayKey = "login-otp-day:{$tail}", 10)) {
            return 'سقف ۱۰ بار درخواست کد در شبانه‌روز برای این شماره پر شده است. فردا دوباره تلاش کنید.';
        }

        if (RateLimiter::hit($coolKey, self::COOLDOWN) > 1) {
            return 'کد را به‌تازگی دریافت کرده‌اید. ' . RateLimiter::availableIn($coolKey) . ' ثانیه دیگر دوباره تلاش کنید.';
        }

        $code = (string) random_int(10000, 99999);

        if (! $sms->code($to, $code)) {
            RateLimiter::clear($coolKey);

            return 'ارسال پیامک ناموفق بود. دوباره تلاش کنید.';
        }

        RateLimiter::hit($hourKey, 3600);
        RateLimiter::hit($dayKey, 86400);
        Cache::put("login-otp:{$tail}", ['user' => $users->first(), 'hash' => hash_hmac('sha256', $code, config('app.key'))], 120);
        RateLimiter::clear("login-otp-try:{$tail}");

        return null;
    }
}
