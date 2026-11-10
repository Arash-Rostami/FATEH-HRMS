<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class VoidLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->void($row, $actor));
    }

    private function void(Correspondence $row, User $actor): void
    {
        if ($row->direction === Correspondence::DIRECTION_IN) {
            LetterFlow::settle($row, $actor, Correspondence::STATUS_VOID, 'voided');

            return;
        }

        CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
        if ($row->status !== Correspondence::STATUS_APPROVED) {
            throw LetterRules::fail('status', 'invalid_state');
        }

        $row->forceFill(['status' => Correspondence::STATUS_VOID]);
        LetterFlow::log($row, $actor, 'voided');
        $row->save();
    }
}
