<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;

class ReleaseTicketAction
{
    public const REPLY_PREFIX = 'بازگشت تیکت به صف: ';

    public function execute(ReplyForm $form, Ticket $ticket, User $actor): void
    {
        abort_if(blank(trim($form->body)), 422, 'توضیح رهاسازی را بنویسید.');

        DB::transaction(function () use ($form, $ticket, $actor) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            abort_unless($locked && TicketAccessPolicy::canRelease($locked, $actor), 403);

            $locked->update(['assigned_to' => null, 'status' => 'open']);
            $locked->addReply($actor, self::REPLY_PREFIX . $form->body);
        });
    }
}
