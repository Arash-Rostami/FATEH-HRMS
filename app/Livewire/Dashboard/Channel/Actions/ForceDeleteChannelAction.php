<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Channel;

class ForceDeleteChannelAction
{
    public function execute(Channel $channel): bool|null
    {
        return $channel->forceDelete();
    }
}