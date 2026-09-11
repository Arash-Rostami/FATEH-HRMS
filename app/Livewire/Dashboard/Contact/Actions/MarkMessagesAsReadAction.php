<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Jobs\ReconcileNudge;
use App\Models\Message;
use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class MarkMessagesAsReadAction
{
    public function execute(int $contactId, int $viewerId): int
    {
        $count = Message::withoutTrashed()
            ->where('sender_id', $contactId)
            ->where('recipient_id', $viewerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        DB::afterCommit(fn() => StateService::flush());
        dispatch(new ReconcileNudge('contacts-controller:nudge', User::class, $contactId))->afterCommit();

        return $count;
    }
}
