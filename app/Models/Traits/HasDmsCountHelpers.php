<?php

namespace App\Models\Traits;

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

    public static function getUnsignedDocumentsCount()
    {
        return self::needsSignCount(auth()->id());
    }
}
