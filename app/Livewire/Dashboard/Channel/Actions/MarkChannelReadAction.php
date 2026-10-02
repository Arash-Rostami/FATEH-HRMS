<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Services\Messaging\PollDigestService;
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

        $changed = DB::transaction(function () use ($channel, $channelId, $userId): bool {
            $enteredNow = $channel->memberUsers()->newPivotStatementForId($userId)
                ->whereNull('entered_at')
                ->update(['entered_at' => now(), 'updated_at' => now()]);

            $lastId = ChannelMessage::lastIdForChannel($channelId);
            $markedRead = 0;

            if ($lastId !== null) {
                $markedRead = $channel->memberUsers()->newPivotStatementForId($userId)
                    ->where(function ($q) use ($lastId) {
                        $q->whereNull('last_read_message_id')
                            ->orWhere('last_read_message_id', '<', $lastId);
                    })
                    ->update(['last_read_message_id' => $lastId, 'updated_at' => now()]);
            }

            return $enteredNow > 0 || $markedRead > 0;
        });

        if ($changed) {
            DB::afterCommit(function () use ($channelId, $userId) {
                PollDigestService::bump("ch:me:{$userId}");
                PollDigestService::bump("ch:rv:{$channelId}");
            });
        }

        dispatch(new ReconcileNudge('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
        dispatch(new ReconcileEdge('channels-controller:edge', Channel::class, $channelId))->afterCommit();
    }
}