<?php


use App\Enums\PresenceStatus;
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
    function presence(mixed $p): ?PresenceStatus
    {
        if ($p instanceof PresenceStatus) return $p;
        if (is_string($p)) return PresenceStatus::tryFrom($p);
        return null;
    }
}

if (!function_exists('presenceCases')) {
    function presenceCases(): array
    {
        return PresenceStatus::cases();
    }
}
