<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
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

        return $ticket->addReply($user, $this->neutralised(trim($form->body)), $form->files);
    }

    private function neutralised(string $body): string
    {
        if ((new TicketPresenter())->replyKind(['body' => $body]) === 'reply') {
            return $body;
        }

        return mb_substr($body, 0, 1) . "\u{2060}" . mb_substr($body, 1);
    }
}
