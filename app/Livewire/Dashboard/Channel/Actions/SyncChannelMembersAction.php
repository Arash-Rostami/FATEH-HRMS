<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncChannelMembersAction
{
    public function execute(int $channelId, int $ownerId, array $recipientIds): array
    {
        $channel = Channel::query()
            ->where('owner_id', $ownerId)
            ->find($channelId);

        if (!$channel) {
            return ['added' => 0, 'removed' => 0];
        }

        $inserted = null;
        $deleted = 0;

        DB::transaction(function () use ($channel, $ownerId, $recipientIds, &$inserted, &$deleted): void {
            $recipientIds = $this->normalizeRecipients($recipientIds, $ownerId);
            $current = $channel->members()->pluck('user_id')->all();

            $toAdd = array_values(array_diff($recipientIds, $current));

            if (!empty($toAdd)) {
                $now = now();
                $rows = array_map(fn(int $userId) => [
                    'channel_id'           => $channel->id,
                    'user_id'              => $userId,
                    'joined_at'            => $now,
                    'last_read_message_id' => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ], $toAdd);

                $inserted = ChannelMember::insertOrIgnore($rows);
            }

            if (empty($recipientIds)) {
                return;
            }

            $toRemove = array_values(array_diff($current, $recipientIds, [$ownerId]));

            if (!empty($toRemove)) {
                $deleted = $channel->members()->whereIn('user_id', $toRemove)->delete();
            }
        });

        return [
            'added'   => is_numeric($inserted) ? (int) $inserted : 0,
            'removed' => (int) $deleted,
        ];
    }

    private function normalizeRecipients(array $recipientIds, int $ownerId): array
    {
        $ids = collect($recipientIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0 && $id !== $ownerId)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return User::query()
            ->active()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }
}