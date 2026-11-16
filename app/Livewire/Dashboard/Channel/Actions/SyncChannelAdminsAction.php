<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Enums\ChannelRole;
use App\Models\Channel;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Support\ChannelAccessPolicy;
use Illuminate\Support\Facades\DB;

class SyncChannelAdminsAction
{
    public function execute(int $channelId, int $actorId, array $adminIds): array
    {
        $channel = Channel::withoutTrashed()->find($channelId);

        if (! $channel || ! ChannelAccessPolicy::canManageAdmins($channel, User::find($actorId))) {
            return ['promoted' => 0, 'demoted' => 0];
        }

        $ownerId = (int) $channel->owner_id;

        $ids = collect($adminIds)
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id < 1 || $id === $ownerId)
            ->unique()
            ->all();

        $admin = ChannelRole::Admin->value;
        $members = fn () => DB::table('channel_members')->where('channel_id', $channelId);

        $result = DB::transaction(function () use ($members, $ids, $admin): array {
            $eligible = $ids
                ? User::query()->visibleOnBoard()->whereKey($ids)->pluck('id')->all()
                : [];

            return [
                'promoted' => $eligible
                    ? $members()
                        ->whereIn('user_id', $eligible)
                        ->whereNotNull('entered_at')
                        ->where('role', '!=', $admin)
                        ->update(['role' => $admin])
                    : 0,
                'demoted' => $members()
                    ->where('role', $admin)
                    ->when($eligible, fn ($q) => $q->whereNotIn('user_id', $eligible))
                    ->update(['role' => ChannelRole::Member->value]),
            ];
        });

        if (array_sum($result)) {
            PollDigestService::bump("ch:rv:{$channelId}");
        }

        return $result;
    }
}
