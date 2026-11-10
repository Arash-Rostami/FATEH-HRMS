<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;

class CloseLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => LetterFlow::settle($row, $actor, Correspondence::STATUS_DONE, 'closed'));
    }
}
