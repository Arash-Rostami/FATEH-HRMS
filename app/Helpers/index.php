<?php


use App\Enums\PresenceStatus;
use App\Models\Permission;
use App\Services\GreetingService;
use App\Services\QuoteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

if (!function_exists('superClean')) {

    function superClean(?string $text, int $limit = 100, bool $nl2br = false): string
    {
        if (empty($text)) return '';

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = ($nl2br)
            ? preg_replace('/[ \t]+/u', ' ', $text)
            : preg_replace('/\s+/u', ' ', $text);
        $text = Str::limit(trim($text), $limit);

        return $nl2br ? nl2br(e($text), false) : $text;
    }
}


if (!function_exists('jdate')) {
    function jdate($date = null)
    {
        if (!$date) return Jalalian::now();

        $instance = $date instanceof Carbon ? $date : Carbon::parse($date);
        return Jalalian::fromCarbon($instance);
    }
}

if (!function_exists('jdateOnly')) {
    function jdateOnly($date = null)
    {
        return jdate($date)->format('%d/%m/%Y');
    }
}

if (!function_exists('isPast')) {
    function isPast(string $time = null): bool
    {
        if (!$time) return false;

        return Carbon::parse($time)->isPast();
    }
}


if (!function_exists('presence')) {
    function presence(mixed $p): PresenceStatus
    {
        if ($p instanceof PresenceStatus) return $p;
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

if (!function_exists('quotes')) {
    function quotes(): array
    {
        return app(QuoteService::class)->all();
    }
}

if (!function_exists('shortGreeting')) {
    function shortGreeting(?string $name = null): string
    {
        $name ??= auth()->user()?->name ?? '';
        return app(GreetingService::class)->getShortGreeting($name);
    }
}


if (!function_exists('isSpecialDay')) {
    function isSpecialDay($type)
    {
        $user = Auth::user();
        if (!$user) return false;

        $cacheKey = $type . $user->id;
        if (cache()->has($cacheKey)) return false;

        $date = null;
        if ($type === 'birthdate' && $user->profile && $user->profile->birthdate) {
            $date = $user->profile->birthdate;
        } elseif ($type === 'start_date' && $user->profile && $user->profile->start_date) {
            $date = $user->profile->start_date;
        }

        if (!$date) return false;

        return $date->format('m-d') === now()->format('m-d');
    }
}

if (!function_exists('getEventStyles')) {
    function getEventStyles($type): string
    {
        return match ($type) {
            'birthday' => 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
            'anniversary' => 'bg-amber-50 text-amber-600 ring-1 ring-amber-100',
            default => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
        };
    }
}

if (!function_exists('formatJalaliDate')) {
    function formatJalaliDate($date, $format = 'l, d F', $fromFormat = 'Y-m-d')
    {
        try {
            return Jalalian::fromFormat($fromFormat, $date)->format($format);
        } catch (\Throwable $e) {
            return $date;
        }
    }
}

if (!function_exists('isVideo')) {
    function isVideo($path): bool
    {
        $extension = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));
        return in_array($extension, ['mp4', 'webm', 'ogg']);
    }
}

if (!function_exists('getFileExtension')) {
    function getFileExtension($path): string
    {
        return strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));
    }
}

if (!function_exists('convertToPersian')) {
    function convertToPersian(?string $string): ?string
    {
        return $string === null ? null : str_replace(
            range(0, 9),
            ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'],
            $string
        );
    }
}

if (!function_exists('toJalali')) {
    function toJalali(mixed $date, string $format = 'Y/m/d H:i'): string
    {
        if (empty($date)) return '';
        if ($date instanceof Carbon) return Jalalian::fromCarbon($date)->format($format);

        $dateString = (string)$date;
        $dateString = str_replace('/', '-', $dateString);
        $year = (int)explode('-', $dateString)[0];

        if ($year >= 1300 && $year <= 1500) return $dateString;

        return Jalalian::fromCarbon(Carbon::parse($dateString))->format($format);
    }
}

if (!function_exists('toJalaliSmart')) {
    function toJalaliSmart(mixed $date): string
    {
        if (blank($date)) return '—';

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon->format('H:i') === '00:00'
            ? toJalali($carbon, 'Y/m/d')
            : toJalali($carbon, 'Y/m/d H:i');
    }
}

if (!function_exists('jNow')) {
    function jNow(string $part = 'year'): int
    {
        return match($part) {
            'month' => jdate()->getMonth(),
            'day'   => jdate()->getDay(),
            default => jdate()->getYear(),
        };
    }
}

if (!function_exists('canAdmin')) {
    function canAdmin(): bool
    {
        if (!auth()->check()) return false;

        $adminPerm = Permission::forUser(auth()->id());

        return $adminPerm && ($adminPerm->is_super_admin || !empty($adminPerm->abilities));
    }
}
