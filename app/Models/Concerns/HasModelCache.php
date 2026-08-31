<?php

namespace App\Models\Concerns;

use App\Services\Cache\ModelCacheVersion;
use Closure;
use DateInterval;
use DateTimeInterface;

trait HasModelCache
{
    protected static function cached(string $suffix, Closure $callback, DateTimeInterface|DateInterval|null $ttl = null): mixed
    {
        return ModelCacheVersion::remember(static::class, $suffix, $ttl ?? now()->addSeconds(ModelCacheVersion::defaultSeconds()), $callback);
    }

    protected static function cachedForUser(int $userId, string $suffix, Closure $callback, DateTimeInterface|DateInterval|null $ttl = null): mixed
    {
        return ModelCacheVersion::remember(static::class, "u{$userId}:{$suffix}", $ttl ?? now()->addSeconds(ModelCacheVersion::viewerSeconds()), $callback);
    }
}