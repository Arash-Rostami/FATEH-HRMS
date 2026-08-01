<?php

use App\Enums\PresenceStatus;
use App\Models\Permission;
use App\Services\GreetingService;
use App\Services\QuoteService;
use App\Services\Reservation\ValidationService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

if (!function_exists('convertToPersian')) {
    function convertToPersian(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strtr((string)$value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'
        ]);
    }
}

if (!function_exists('superClean')) {
    function superClean(?string $text, int $limit = 100, bool $nl2br = false): string
    {
        if (blank($text)) {
            return '';
        }

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace($nl2br ? '/[ \t]+/u' : '/\s+/u', ' ', $text);
        $text = Str::limit(trim((string)$text), $limit);

        return $nl2br ? nl2br(e($text), false) : $text;
    }
}

if (!function_exists('renderComment')) {
    function renderComment(?string $text, int $limit = 2000): string
    {
        if (blank($text)) {
            return '';
        }

        $text = html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = Str::limit(trim($text), $limit);
        $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);
        $text = Str::sanitizeHtml((string)$text);

        return nl2br($text, false);
    }
}

if (!function_exists('jdate')) {
    function jdate(mixed $date = null): Jalalian
    {
        if (blank($date)) {
            return Jalalian::now();
        }

        try {
            $instance = $date instanceof CarbonInterface
                ? $date
                : Carbon::parse($date);

            return Jalalian::fromCarbon($instance);
        } catch (\Throwable $e) {
            return Jalalian::now();
        }
    }
}

if (!function_exists('jdateOnly')) {
    function jdateOnly(mixed $date = null): string
    {
        return convertToPersian(jdate($date)->format('%d/%m/%Y')) ?? '';
    }
}

if (!function_exists('isPast')) {
    function isPast(?string $time = null): bool
    {
        if (blank($time)) {
            return false;
        }

        try {
            return Carbon::parse($time)->isPast();
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('presence')) {
    function presence(mixed $p): PresenceStatus
    {
        if ($p instanceof PresenceStatus) {
            return $p;
        }

        return PresenceStatus::tryFrom((string)$p) ?? PresenceStatus::Onsite;
    }
}

if (!function_exists('presenceCases')) {
    function presenceCases(): array
    {
        return PresenceStatus::cases();
    }
}

if (!function_exists('greeting')) {
    function greeting(?string $name = null): string
    {
        $name ??= auth()->user()?->name ?? '';
        return app(GreetingService::class)->getGreeting($name);
    }
}

if (!function_exists('shortGreeting')) {
    function shortGreeting(?string $name = null): string
    {
        $name ??= auth()->user()?->name ?? '';
        return app(GreetingService::class)->getShortGreeting($name);
    }
}

if (!function_exists('quotes')) {
    function quotes(): array
    {
        return app(QuoteService::class)->all();
    }
}


if (!function_exists('isSpecialDay')) {
    function isSpecialDay(string $type): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (cache()->has($type . '_' . $user->id)) {
            return false;
        }

        $user->loadMissing('profile');

        $date = match ($type) {
            'birthdate' => $user->profile?->birthdate,
            'start_date' => $user->profile?->start_date,
            default => null,
        };

        if (!$date instanceof CarbonInterface) {
            return false;
        }

        return $date->format('m-d') === Carbon::now()->format('m-d');
    }
}

if (!function_exists('getEventStyles')) {
    function getEventStyles(string $type): string
    {
        return match ($type) {
            'birthday' => 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
            'anniversary' => 'bg-amber-50 text-amber-600 ring-1 ring-amber-100',
            'holiday' => 'bg-rose-50 text-rose-600 ring-1 ring-rose-100',
            default => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
        };
    }
}

if (!function_exists('formatJalaliDate')) {
    function formatJalaliDate(mixed $date, string $format = 'l, d F', string $fromFormat = 'Y-m-d'): string
    {
        if (blank($date)) {
            return '';
        }

        try {
            $formatted = Jalalian::fromFormat($fromFormat, (string)$date)->format($format);
            return convertToPersian($formatted) ?? (string)$date;
        } catch (\Throwable $e) {
            return convertToPersian((string)$date) ?? (string)$date;
        }
    }
}

if (!function_exists('isVideo')) {
    function isVideo(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['mp4', 'webm', 'ogg'], true);
    }
}

if (!function_exists('getFileExtension')) {
    function getFileExtension(?string $path): string
    {
        if (blank($path)) {
            return '';
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}

if (!function_exists('toJalali')) {
    function toJalali(mixed $date, string $format = 'Y/m/d H:i'): string
    {
        if (blank($date)) {
            return '';
        }

        try {
            if ($date instanceof CarbonInterface) {
                return convertToPersian(Jalalian::fromCarbon($date)->format($format)) ?? '';
            }

            $dateString = str_replace('/', '-', (string)$date);
            $year = (int)explode('-', $dateString)[0];

            if ($year >= 1300 && $year <= 1500) {
                return convertToPersian($dateString) ?? $dateString;
            }

            return convertToPersian(Jalalian::fromCarbon(Carbon::parse($dateString))->format($format)) ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('toJalaliSmart')) {
    function toJalaliSmart(mixed $date): string
    {
        if (blank($date)) {
            return '—';
        }

        try {
            $carbon = $date instanceof CarbonInterface
                ? $date
                : Carbon::parse($date);

            $format = $carbon->format('H:i') === '00:00' ? 'Y/m/d' : 'Y/m/d H:i';

            return toJalali($carbon, $format);
        } catch (\Throwable $e) {
            return '—';
        }
    }
}

if (!function_exists('toJalaliRelative')) {
    function toJalaliRelative(mixed $date, bool $short = false): string
    {
        if (blank($date)) {
            return '';
        }

        try {
            $carbon = $date instanceof CarbonInterface
                ? $date
                : Carbon::parse($date);
        } catch (\Throwable $e) {
            return '';
        }

        $seconds = $carbon->diffInSeconds(Carbon::now());

        if ($seconds < 60) {
            return $short ? 'الان' : 'همین الان';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            $pMinutes = convertToPersian($minutes);
            return $short ? $pMinutes . 'د' : $pMinutes . ' دقیقه پیش';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            $pHours = convertToPersian($hours);
            return $short ? $pHours . 'س' : $pHours . ' ساعت پیش';
        }

        $days = intdiv($hours, 24);
        if ($days < 7) {
            $pDays = convertToPersian($days);
            return $short ? $pDays . 'ر' : $pDays . ' روز پیش';
        }

        if ($days < 30) {
            $weeks = intdiv($days, 7);
            $pWeeks = convertToPersian($weeks);
            return $short ? $pWeeks . 'ه' : $pWeeks . ' هفته پیش';
        }

        return toJalali($carbon, 'Y/m/d');
    }
}

if (!function_exists('jNow')) {
    function jNow(string $part = 'year'): int
    {
        $jalalian = jdate();

        return match ($part) {
            'month' => $jalalian->getMonth(),
            'day' => $jalalian->getDay(),
            default => $jalalian->getYear(),
        };
    }
}

if (!function_exists('canAdmin')) {
    function canAdmin(): bool
    {
        static $status = [];
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (isset($status[$user->id])) {
            return $status[$user->id];
        }

        // Developers are super-admin by role — no permission row needed.
        if ($user->isDeveloper()) {
            return $status[$user->id] = true;
        }

        $adminPerm = Permission::forUser($user->id);

        return $status[$user->id] = $adminPerm && ($adminPerm->is_super_admin || !empty($adminPerm->abilities));
    }
}

if (!function_exists('disabledReservationTypes')) {
    function disabledReservationTypes(): array
    {
        return app(ValidationService::class)->disabledTypes();
    }
}

if (!function_exists('tenantAsset')) {
    function tenantAsset(string $tenant, string $type, string $role, ?string $fallback = null): ?string
    {
        $matches = glob(resource_path("assets/{$type}/{$tenant}/{$role}.*")) ?: [];

        return $matches ? "build/assets/{$type}/{$tenant}/" . basename($matches[0]) : $fallback;
    }
}

if (!function_exists('tenantVideos')) {
    function tenantVideos(string $tenant): array
    {
        $files = glob(resource_path("assets/video/{$tenant}/*.*")) ?: [];
        natsort($files);

        return array_map(
            fn(string $file): string => "build/assets/video/{$tenant}/" . basename($file),
            array_values($files)
        );
    }
}

if (!function_exists('tenantLogo')) {
    function tenantLogo(bool $dark, string $scope = 'user'): string
    {
        if ($scope === 'admin' && config('app.admin_use_company_logo')) {
            return config('app.company_logo');
        }

        $reversed = config("app.{$scope}_reverse_logo");
        $showDark = $scope === 'admin' && $reversed
            ? $dark === request()->routeIs('filament.admin.auth.login')
            : ($reversed ? !$dark : $dark);

        return config($showDark ? 'app.app_logo_dark' : 'app.app_logo_light');
    }
}
