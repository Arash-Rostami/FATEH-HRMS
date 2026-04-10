<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Livewire\Dashboard\Contact\Forms\EditMessageForm;
use App\Models\Message;

class SaveEditAction
{
    public function execute(EditMessageForm $form, int $messageId, int $editTimeLimit): bool
    {
        $form->validate();

        $message = Message::withoutTrashed()
            ->where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->first();

        if (!$message || $message->created_at->diffInSeconds(now()) > $editTimeLimit) {
            return false;
        }

        $message->update(['body' => trim($form->editingBody), 'is_edited' => true]);

        return true;
    }
}
