<?php

namespace App\Services\ProjectTask;

use Illuminate\Support\Facades\Cache;

class ProjectHeartbeat
{
    public const DOMAINS = ['activity', 'chat', 'task'];

    public static function bump(int $projectId, string $domain = 'activity'): void
    {
        Cache::put(self::key($projectId, $domain), microtime(true), now()->addDay());
    }

    public static function version(int $projectId, string $domain = 'activity'): string
    {
        return (string) Cache::get(self::key($projectId, $domain), '0');
    }

    public static function versions(int $projectId): array
    {
        $keys = collect(self::DOMAINS)->mapWithKeys(fn(string $domain) => [$domain => self::key($projectId, $domain)]);
        $cached = Cache::many($keys->values()->all());

        return $keys->mapWithKeys(fn(string $key, string $domain) => [$domain => (string) ($cached[$key] ?? '0')])->all();
    }

    private static function key(int $projectId, string $domain): string
    {
        return "project:{$projectId}:v:{$domain}";
    }
}
