<?php

namespace App\Services\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModelCacheVersion
{
    private const PREFIX = 'model_cache_version';

    public static function remember(string $modelClass, string $suffix, DateTimeInterface|DateInterval $ttl, Closure $callback): mixed
    {
        return Cache::remember(self::key($modelClass, $suffix), $ttl, $callback);
    }

    public static function rememberGlobal(string $key, Closure $callback, DateTimeInterface|DateInterval|null $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? now()->addSeconds(self::defaultSeconds()), $callback);
    }

    public static function defaultSeconds(): int
    {
        return (int) config('app.cache_ttl', 300);
    }

    public static function viewerSeconds(): int
    {
        $pref = (int) (Auth::user()?->extra['preferences']['cache_ttl'] ?? 0);

        return max(self::defaultSeconds(), min(3600, $pref));
    }

    public static function bump(string $modelClass): void
    {
        try {
            Cache::forever(self::versionKey($modelClass), now()->format('Uu') . '.' . random_int(100, 999));
        } catch (Throwable $e) {
            Log::warning('ModelCacheVersion: failed to bump version', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function key(string $modelClass, string $suffix): string
    {
        return "{$modelClass}:v" . self::version($modelClass) . ":{$suffix}";
    }

    public static function version(string $modelClass): string
    {
        try {
            return (string) Cache::get(self::versionKey($modelClass), '0');
        } catch (Throwable $e) {
            Log::warning('ModelCacheVersion: failed to read version, defaulting to 0', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);

            return '0';
        }
    }

    private static function versionKey(string $modelClass): string
    {
        return self::PREFIX . ':' . $modelClass;
    }
}
