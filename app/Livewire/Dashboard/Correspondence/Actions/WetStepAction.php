<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class WetStepAction
{
    public function execute(int $id, array $payload = []): void
    {
        $step = $payload['step'] ?? null;
        in_array($step, ['printed', 'wet_signed'], true) || LetterRules::deny();

        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->wet($row, $actor, $step));
    }

    private function wet(Correspondence $row, User $actor, string $action): void
    {
        CorrespondenceAccessPolicy::canSteward($actor, $row) || LetterRules::deny();
        if ($row->status !== Correspondence::STATUS_APPROVED || $row->sign_mode !== Correspondence::SIGN_MODE_WET) {
            throw LetterRules::fail('status', 'invalid_state');
        }
        if (LetterFlow::hasEvent($row, $action)) {
            return;
        }

        LetterFlow::log($row, $actor, $action);
        $row->save();
    }
}
