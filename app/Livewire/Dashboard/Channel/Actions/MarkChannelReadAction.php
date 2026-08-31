<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Channel;
use App\Models\ChannelMessage;
use Illuminate\Support\Facades\DB;

class MarkChannelReadAction
{
    public function execute(int $channelId, int $userId): void
    {
        $channel = Channel::withoutTrashed()
            ->whereHas('memberUsers', fn($q) => $q->where('users.id', $userId))
            ->find($channelId);

        if (!$channel) {
            return;
        }

        DB::transaction(function () use ($channel, $channelId, $userId): void {
            $channel->memberUsers()->newPivotStatementForId($userId)
                ->whereNull('entered_at')
                ->update(['entered_at' => now(), 'updated_at' => now()]);

            $lastId = ChannelMessage::lastIdForChannel($channelId);

            if ($lastId !== null) {
                $channel->memberUsers()->newPivotStatementForId($userId)
                    ->where(function ($q) use ($lastId) {
                        $q->whereNull('last_read_message_id')
                            ->orWhere('last_read_message_id', '<', $lastId);
                    })
                    ->update(['last_read_message_id' => $lastId, 'updated_at' => now()]);
            }
        });

        dispatch(new ReconcileNudge('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
        dispatch(new ReconcileEdge('channels-controller:edge', Channel::class, $channelId))->afterCommit();
    }
}