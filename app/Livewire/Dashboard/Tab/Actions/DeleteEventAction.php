<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Event;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteEventAction
{
    public function execute(?int $eventId, int $userId): void
    {
        if ($eventId) {
            Event::where('user_id', $userId)
                ->where('id', $eventId)
                ->delete();

            DB::afterCommit(function (): void {
                Cache::forget('countdown:active');
                StateService::flush();
            });
        }
    }
}
