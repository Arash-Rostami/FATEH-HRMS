<?php

namespace App\Livewire\Dashboard\Contact\Actions;

use App\Livewire\Dashboard\Contact\Forms\MessageComposerForm;
use App\Models\Message;

class SendMessageAction
{
    public function execute(MessageComposerForm $form, int $recipientId, ?int $replyToId): Message
    {
        $form->validate();

        return Message::create([
            'sender_id'    => auth()->id(),
            'recipient_id' => $recipientId,
            'body'         => trim($form->body),
            'reply_to_id'  => $replyToId,
        ]);
    }
}
