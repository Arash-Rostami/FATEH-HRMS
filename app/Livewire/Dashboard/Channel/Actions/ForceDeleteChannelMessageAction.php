<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\ChannelMessage;

class ForceDeleteChannelMessageAction
{
    public function execute(ChannelMessage $message): bool|null
    {
        return $message->forceDelete();
    }
}