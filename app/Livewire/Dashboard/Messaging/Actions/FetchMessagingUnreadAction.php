<?php

namespace App\Livewire\Dashboard\Messaging\Actions;

use App\Models\ChannelMessage;
use App\Models\Message;

class FetchMessagingUnreadAction
{
    public function execute(int $userId): array
    {
        return [
            'contacts' => Message::totalUnreadFor($userId),
            'channels' => ChannelMessage::totalUnreadFor($userId),
        ];
    }
}