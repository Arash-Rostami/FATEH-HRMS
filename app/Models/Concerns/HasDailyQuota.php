<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

trait HasDailyQuota
{
    public static function quotaKey(int $userId, ?string $day = null): string
    {
        return "ai-report:quota:{$userId}:" . ($day ?? now()->format('Y-m-d'));
    }

    public static function quotaCap(): int
    {
        return (int) config('ai_report.daily_cap');
    }

    public static function quotaUsed(int $userId): int
    {
        return (int) Cache::get(self::quotaKey($userId), 0);
    }

    public static function consumeQuota(int $userId): int
    {
        Cache::add($key = self::quotaKey($userId), 0, now()->endOfDay());

        return Cache::increment($key);
    }

    public static function refundQuota(int $userId, ?string $day = null): void
    {
        $key = self::quotaKey($userId, $day);
        if ((int) Cache::get($key, 0) >= 1) {
            if (Cache::decrement($key) < 0) {
                Cache::increment($key);
            }
        }
    }
}
