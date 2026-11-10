<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class SupersedeLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->supersede($row, $actor, $payload));
    }

    private function supersede(Correspondence $row, User $actor, array $payload): void
    {
        CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
        if ($row->status !== Correspondence::STATUS_APPROVED) {
            throw LetterRules::fail('status', 'invalid_state');
        }

        $replacementId = is_numeric($payload['replacement_id'] ?? null) ? (int) $payload['replacement_id'] : 0;
        $replacement = $replacementId > 0 && $replacementId !== (int) $row->id
            ? Correspondence::query()->whereKey($replacementId)->lockForUpdate()->first()
            : null;

        if ($replacement === null
            || !CorrespondenceAccessPolicy::canView($actor, $replacement)
            || (int) $replacement->supersedes_id !== (int) $row->id
            || $replacement->direction !== $row->direction
            || $replacement->direction !== Correspondence::DIRECTION_OUT
            || $replacement->status !== Correspondence::STATUS_APPROVED) {
            LetterRules::deny();
        }

        $row->forceFill(['status' => Correspondence::STATUS_SUPERSEDED]);
        LetterFlow::log($row, $actor, 'superseded', ['target' => filled($replacement->number) ? (string) $replacement->number : '#' . $replacement->id]);
        $row->save();
    }
}
