<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Models\Message;

class ForceDeleteMessageAction
{
    public function execute(Message $message): bool|null
    {
        return $message->forceDelete();
    }
}