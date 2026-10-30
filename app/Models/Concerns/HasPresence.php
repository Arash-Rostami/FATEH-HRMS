<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasPresence
{
    public function isOnline(): bool
    {
        return $this->last_seen !== null && $this->last_seen->gte(static::onlineSince());
    }

    public function lastSeenLabel(): string
    {
        return match (true) {
            $this->isOnline() => 'آنلاین',
            $this->last_seen === null => 'هرگز',
            default => toJalaliRelative($this->last_seen),
        };
    }

    public static function onlineSince(): Carbon
    {
        return now()->subMinutes(static::ONLINE_MINUTES);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('users.last_seen', '>=', static::onlineSince());
    }

    public function touchLastSeen(): void
    {
        $was = $this->timestamps;
        $this->timestamps = false;
        try {
            $this->forceFill(['last_seen' => now()])->saveQuietly();
        } finally {
            $this->timestamps = $was;
        }
    }

    public function markOffline(): void
    {
        $was = $this->timestamps;
        $this->timestamps = false;
        try {
            $this->forceFill(['last_seen' => now()->subMinutes(static::ONLINE_MINUTES + 1)])->saveQuietly();
            Cache::forget('last_seen:' . $this->id);
        } finally {
            $this->timestamps = $was;
        }
    }
}