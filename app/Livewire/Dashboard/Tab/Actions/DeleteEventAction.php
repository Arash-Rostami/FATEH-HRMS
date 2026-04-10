<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Event;

class DeleteEventAction
{
    public function execute(?int $eventId, int $userId): void
    {
        if ($eventId) {
            Event::where('user_id', $userId)
                ->where('id', $eventId)
                ->delete();
        }
    }
}
