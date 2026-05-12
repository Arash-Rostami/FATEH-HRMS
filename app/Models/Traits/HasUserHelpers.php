<?php

namespace App\Models\Traits;

use App\Models\Read;
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

    /* ─────────────────────────────────────────────
       For Read (single user_id lookup)
       ───────────────────────────────────────────── */

    public function getUserNameTooltipAttribute(): ?string
    {
        $userId = $this->user_id ?? null;

        if (empty($userId)) {
            return null;
        }

        return static::userNames()[$userId] ?? null;
    }


    public static function getReaderNamesTooltipForDocument(int $documentId): ?string
    {
        static $documentReaders = [];

        if (!array_key_exists($documentId, $documentReaders)) {
            $documentReaders[$documentId] = Read::where('document_id', $documentId)
                ->pluck('user_id')
                ->unique()
                ->values()
                ->toArray();
        }

        $readerIds = $documentReaders[$documentId];

        if (empty($readerIds)) {
            return null;
        }

        $names = array_filter(
            array_map(
                fn ($id) => static::userNames()[$id] ?? null,
                $readerIds
            )
        );

        return implode(' ┆ ', $names) ?: null;
    }
}
