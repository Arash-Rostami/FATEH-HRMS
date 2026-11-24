<?php

namespace App\Models\Concerns;

use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Cache;

trait HasDailyQuota
{
    protected static function quotaFeature(): string
    {
        return 'report';
    }

    public static function quotaKey(int $userId, ?string $day = null): string
    {
        return 'ai-' . static::quotaFeature() . ":quota:{$userId}:" . ($day ?? now()->format('Y-m-d'));
    }

    public static function quotaCap(): int
    {
        return (int) AiClient::setting(static::quotaFeature(), 'daily_cap');
    }

    public static function quotaUsed(int $userId): int
    {
        return (int) Cache::get(static::quotaKey($userId), 0);
    }

    public static function quotaMeter(int $userId): array
    {
        $cap = static::quotaCap();
        $used = min(static::quotaUsed($userId), $cap);
        $pct = min(100, max(0, (int) round($used * 100 / max(1, $cap))));

        return [
            'used' => $used,
            'cap' => $cap,
            'pct' => $pct,
            'tone' => match (true) {
                $used >= $cap => ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]'],
                $pct >= 80 => ['text' => 'text-[var(--tool-gold-color)]', 'fill' => 'bg-[var(--tool-gold-color)]'],
                default => ['text' => 'text-[var(--md-sys-color-on-surface-variant)]', 'fill' => 'bg-[var(--ai-color)]'],
            },
        ];
    }

    public static function consumeQuota(int $userId, ?string $day = null): int
    {
        Cache::add($key = static::quotaKey($userId, $day), 0, $day === null ? now()->endOfDay() : now()->endOfDay()->addDay());

        return Cache::increment($key);
    }

    public static function refundQuota(int $userId, ?string $day = null): void
    {
        $key = static::quotaKey($userId, $day);
        if ((int) Cache::get($key, 0) >= 1) {
            if (Cache::decrement($key) < 0) {
                Cache::increment($key);
            }
        }
    }
}
