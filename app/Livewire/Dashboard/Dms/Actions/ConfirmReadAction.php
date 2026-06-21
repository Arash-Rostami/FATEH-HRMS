<?php

namespace App\Livewire\Dashboard\Dms\Actions;

use App\Models\DMS;

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

        if ($increment) {
            $readRecord->increment('read_count');
            $document->increment('combined_read_count');
        }

        return true;
    }
}
