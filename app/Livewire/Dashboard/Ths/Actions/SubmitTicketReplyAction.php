<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;

class SubmitTicketReplyAction
{
    public function execute(ReplyForm $form, Ticket $ticket, User $user): Reply
    {
        abort_unless(TicketAccessPolicy::canReply($ticket, $user), 403);

        $form->validate();

        return $ticket->addReply($user, $form->body, $form->files);
    }
}
