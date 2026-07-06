<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Livewire\Dashboard\Channel\Forms\EditChannelMessageForm;
use App\Models\ChannelMessage;

class SaveEditChannelMessageAction
{
    public const EDIT_TIME_LIMIT = 300;

    public function execute(EditChannelMessageForm $form, int $messageId): bool
    {
        $form->validate();

        $message = ChannelMessage::withoutTrashed()
            ->where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->first();

        if (!$message || $message->created_at->diffInSeconds(now()) > self::EDIT_TIME_LIMIT) {
            return false;
        }

        $message->update(['body' => trim($form->editingBody), 'is_edited' => true]);

        return true;
    }
}