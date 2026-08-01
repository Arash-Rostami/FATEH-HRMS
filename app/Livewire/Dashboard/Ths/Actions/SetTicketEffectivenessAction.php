<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;

class SetTicketEffectivenessAction
{
    public function execute(Ticket $ticket, string $score, User $actor): Ticket
    {
        abort_unless(TicketAccessPolicy::canSetEffectiveness($ticket, $actor), 403);
        abort_unless(in_array($score, ['1', '2', '3', '4', '5'], true), 422);

        $ticket->update(['effectiveness' => $score]);

        return $ticket->fresh();
    }
}
