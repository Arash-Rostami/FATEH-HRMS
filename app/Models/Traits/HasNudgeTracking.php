<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

trait HasNudgeTracking
{
    public const FRESHNESS_DAYS = 30;

    public function isFresh(): bool
    {
        return $this->created_at !== null
            && $this->created_at->gt(now()->subDays(static::FRESHNESS_DAYS));
    }

    public static function hasUnreadFor(int $userId): bool
    {
        return static::nudgeQuery($userId)
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subDays(static::FRESHNESS_DAYS))
            ->exists();
    }

    public static function markReadFor(int $itemId, int $userId): int
    {
        return static::nudgeQuery($userId)
            ->where('data->item_id', $itemId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function seenIdsFor(int $userId): Collection
    {
        return static::nudgeQuery($userId)
            ->whereNotNull('read_at')
            ->where('created_at', '>=', now()->subDays(static::FRESHNESS_DAYS))
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.item_id')) AS item_id")
            ->pluck('item_id')
            ->filter(fn($id) => $id !== null && $id !== '')
            ->mapWithKeys(fn($id) => [(int)$id => true]);
    }

    protected static function nudgeQuery(int $userId): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->where('data->menu_key', static::NUDGE_KEY);
    }
}
