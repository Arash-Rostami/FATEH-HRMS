<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

trait HasCountdown
{
    public static function activeCountdownEvent(): array
    {
        return Cache::remember('countdown:active', 60, function (): array {
            $active = static::query()
                ->where('private', false)
                ->where('date', '>=', now())
                ->where('countdown->enabled', true)
                ->orderBy('date', 'asc')
                ->first(['id', 'title', 'date', 'countdown']);

            if (!$active) {
                return [];
            }

            $mood = ($active->countdown['mood'] ?? 'happy') === 'mourning' ? 'mourning' : 'happy';
            $confetti = (bool)($active->countdown['confetti'] ?? true);
            $messages = array_values(array_filter(
                $active->countdown['messages'] ?? [],
                fn(mixed $m): bool => is_string($m) && trim($m) !== ''
            ));

            return [
                'id' => $active->id,
                'title' => $active->title,
                'date_iso' => $active->date->toIso8601String(),
                'mood' => $mood,
                'confetti' => $confetti,
                'messages' => $messages,
            ];
        });
    }

    public static function packCountdown(array $data): array
    {
        $messages = array_values(array_filter(
            array_map(fn(mixed $item): ?string => static::extractCountdownMessage($item), $data['messages'] ?? [])
        ));

        $data['countdown'] = [
            'enabled' => (bool)($data['enabled'] ?? false),
            'mood' => ($data['mood'] ?? 'happy') === 'mourning' ? 'mourning' : 'happy',
            'confetti' => (bool)($data['confetti'] ?? true),
            'messages' => $messages,
        ];

        unset($data['enabled'], $data['mood'], $data['confetti'], $data['messages']);

        return $data;
    }

    public static function unpackCountdown(array $data): array
    {
        $countdown = is_array($data['countdown'] ?? null) ? $data['countdown'] : [];

        $messages = array_values(array_filter(
            array_map(fn(mixed $item): ?string => static::extractCountdownMessage($item), $countdown['messages'] ?? [])
        ));

        $data['enabled'] = (bool)($countdown['enabled'] ?? false);
        $data['mood'] = ($countdown['mood'] ?? 'happy') === 'mourning' ? 'mourning' : 'happy';
        $data['confetti'] = (bool)($countdown['confetti'] ?? true);
        $data['messages'] = array_map(fn(string $m): array => ['message' => $m], $messages);

        unset($data['countdown']);

        return $data;
    }

    protected static function bootHasCountdown(): void
    {
        $clearCache = fn() => DB::afterCommit(function (): void {
            try {
                Cache::forget('countdown:active');
            } catch (Throwable $e) {
                report($e);
            }
        });

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    private static function extractCountdownMessage(mixed $item): ?string
    {
        $text = match (true) {
            is_string($item) => $item,
            is_array($item) && isset($item['message']) && is_string($item['message']) => $item['message'],
            default => '',
        };

        $text = trim($text);

        return $text !== '' ? $text : null;
    }
}
