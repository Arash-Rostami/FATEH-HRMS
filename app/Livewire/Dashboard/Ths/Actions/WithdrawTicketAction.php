<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;

class WithdrawTicketAction
{
    public function execute(Ticket $ticket, User $actor): void
    {
        DB::transaction(function () use ($ticket, $actor) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            abort_unless($locked && TicketAccessPolicy::canWithdraw($locked, $actor), 403);

            $locked->update(['status' => 'closed', 'action_result' => Ticket::WITHDRAWN_RESULT]);
        });
    }
}
