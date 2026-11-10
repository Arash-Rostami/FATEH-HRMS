<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class WithdrawLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->withdraw($row, $actor));
    }

    private function withdraw(Correspondence $row, User $actor): void
    {
        CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
        if ($row->status !== Correspondence::STATUS_SHARED) {
            throw LetterRules::fail('status', 'not_awaiting');
        }

        $meta = (array) ($row->meta ?? []);
        unset($meta['resume']);

        $row->forceFill([
            'parties' => LetterFlow::withoutSignatures(LetterFlow::parties($row)),
            'status' => Correspondence::STATUS_DRAFT,
            'awaiting_user_id' => null,
            'shared_at' => null,
            'meta' => $meta ?: null,
        ]);
        LetterFlow::writeSteps($row);
        LetterFlow::log($row, $actor, 'withdrawn');
        $row->save();
    }
}
