<?php

namespace App\Models\Concerns;

use App\Models\User;

trait HasUserHelpers
{
    /**
     * Shared request-level cache for ALL user names.
     * Method-static = shared across every class that uses this trait.
     */
    protected static function userNames(): array
    {
        return User::getCachedNames()->toArray();
    }

    /* ─────────────────────────────────────────────
       For DMS (models that store a `users` array)
       ───────────────────────────────────────────── */

    public function getUserNamesTooltipAttribute(): ?string
    {
        $userIds = $this->users ?? [];

        if (empty($userIds)) {
            return null;
        }

        $names = array_filter(
            array_map(
                fn ($id) => static::userNames()[$id] ?? null,
                $userIds
            )
        );

        return implode(' ┆ ', $names) ?: null;
    }

    public function getUsersCountAttribute(): int
    {
        return count($this->users ?? []);
    }
}
