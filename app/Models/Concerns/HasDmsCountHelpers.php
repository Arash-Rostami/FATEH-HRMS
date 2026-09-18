<?php

namespace App\Models\Concerns;

use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait HasDmsCountHelpers
{
    public static function getDocumentCounts()
    {
        return Cache::remember('dms_document_counts', 900, function () {
            return DB::table('dms')->selectRaw("
                COUNT(CASE WHEN status = 'live' THEN 1 END) AS live_count,
                COUNT(CASE WHEN status = 'under_review' THEN 1 END) AS under_review_count,
                COUNT(CASE WHEN status = 'obsolete' THEN 1 END) AS archived_count
            ")->first();
        });
    }

    /**
     * [needsSign, needsRead] for a user, versioned so any DMS write
     * invalidates it; a user's own read-confirmation forgets the key directly.
     */
    public static function pendingCounts(int $userId): array
    {
        return ModelCacheVersion::remember(self::class, "pending_counts:{$userId}", now()->addMinutes(15), function () use ($userId) {
            $dept = \App\Models\User::with('profile')->find($userId)?->profile?->department_id;

            return [
                static::needsSignCount($userId, $dept),
                static::needsReadCount($userId, $dept),
            ];
        });
    }

    public static function getUnsignedDocumentsCount()
    {
        return self::needsSignCount(auth()->id());
    }
}
