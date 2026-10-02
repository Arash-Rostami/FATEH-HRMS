<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Cache;

class PollDigestService
{
    public static function bump(string $key): void
    {
        Cache::put($key, microtime(true), now()->addDay());
    }

    public static function version(string $key): string
    {
        return (string) Cache::get($key, '0');
    }

    public static function versions(array $keys): array
    {
        if (!$keys) {
            return [];
        }

        $cached = Cache::many($keys);

        return array_map(fn($v) => (string) ($v ?? '0'), $cached);
    }
}
