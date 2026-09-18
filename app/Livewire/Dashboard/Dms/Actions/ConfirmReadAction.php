<?php

namespace App\Livewire\Dashboard\Dms\Actions;

use App\Models\DMS;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;

class ConfirmReadAction
{
    public function execute(int $docId, bool $increment = false): bool
    {
        $document = DMS::visibleToUser()->find($docId);

        if (!$document) return false;

        $readRecord = $document->reads()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['read' => true]
        );

        Cache::forget(ModelCacheVersion::key(DMS::class, 'pending_counts:' . auth()->id()));

        if ($increment) {
            $readRecord->increment('read_count');
            $document->increment('combined_read_count');
        }

        return true;
    }
}
