<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Jobs\ReconcileEdgeJob;
use App\Jobs\ReconcileNudgeJob;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\NudgeService;
use App\Services\Messaging\PollDigestService;
use Illuminate\Support\Facades\DB;

class MarkChannelReadAction
{
    public function execute(int $channelId, int $userId): void
    {
        $channel = Channel::withoutTrashed()
            ->whereHas('memberUsers', fn ($q) => $q->whereKey($userId))
            ->find($channelId);

        if (! $channel) {
            return;
        }

        $changed = DB::transaction(function () use ($channel, $channelId, $userId): bool {
            $now = now();
            $pivot = fn () => $channel->memberUsers()->newPivotStatementForId($userId);

            $entered = $pivot()
                ->whereNull('entered_at')
                ->update(['entered_at' => $now, 'updated_at' => $now]);

            $lastId = ChannelMessage::lastIdForChannel($channelId);

            $read = $lastId === null ? 0 : $pivot()
                ->where(fn ($q) => $q->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $lastId))
                ->update(['last_read_message_id' => $lastId, 'updated_at' => $now]);

            return $entered > 0 || $read > 0;
        });

        if (! $changed) {
            return;
        }

        ModelCacheVersion::bump("channel-read:{$userId}");
        NudgeService::refreshBell();

        DB::afterCommit(function () use ($channelId, $userId) {
            PollDigestService::bump("ch:me:{$userId}");
            PollDigestService::bump("ch:rv:{$channelId}");
        });

        dispatch(new ReconcileNudgeJob('channels-controller:nudge', Channel::class, $channelId))->afterCommit();
        dispatch(new ReconcileEdgeJob('channels-controller:edge', Channel::class, $channelId))->afterCommit();
    }
}