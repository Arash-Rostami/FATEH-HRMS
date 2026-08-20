<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModelCacheVersion
{
    private const PREFIX = 'model_cache_version';

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
