<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;

class DeleteDraftAction
{
    public function execute(User $user, array $changes, Correspondence $letter): Correspondence
    {
        return LetterWrite::run($user, ['delete' => true, ...$changes], $letter, fn (Correspondence $fresh) => $fresh->delete());
    }
}
