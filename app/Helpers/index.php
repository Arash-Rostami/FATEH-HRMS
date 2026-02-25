<?php


use App\Enums\PresenceStatus;
use App\Services\GreetingService;
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
