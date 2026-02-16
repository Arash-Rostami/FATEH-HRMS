<?php


use Illuminate\Support\Str;

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
