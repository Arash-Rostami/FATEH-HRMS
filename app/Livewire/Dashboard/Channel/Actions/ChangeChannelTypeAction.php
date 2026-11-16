<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Support\ChannelAccessPolicy;

class ChangeChannelTypeAction
{
    public function execute(int $channelId, int $userId, string $type): bool
    {
        $target = ChannelType::tryFrom($type);
        $channel = Channel::withoutTrashed()->find($channelId);

        if (!$target || !$channel || !ChannelAccessPolicy::canChangeType($channel, User::find($userId)) || $channel->type === $target) {
            return false;
        }

        $channel->update(['type' => $target]);

        PollDigestService::bump("ch:rv:{$channelId}");

        return true;
    }
}
