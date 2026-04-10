<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\RatingForm;
use App\Models\Ticket;

class SubmitRatingAction
{
    public function execute(RatingForm $form, Ticket $ticket): void
    {
        $form->validate();

        $ticket->update([
            'satisfaction_score' => $form->score,
            'extra' => array_merge($ticket->extra ?? [], [
                'satisfaction_comment' => $form->comment,
            ]),
        ]);
    }
}
