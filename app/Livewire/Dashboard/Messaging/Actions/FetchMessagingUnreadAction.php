<?php

namespace App\Livewire\Dashboard\Messaging\Actions;

use App\Models\ChannelMessage;
use App\Models\Message;

class FetchMessagingUnreadAction
{
    public function execute(int $userId): array
    {
        return [
            'contacts' => tenantOff('contact') ? 0 : Message::totalUnreadFor($userId),
            'channels' => tenantOff('channel') ? 0 : ChannelMessage::totalUnreadFor($userId),
        ];
    }
}