<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class RejectLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->reject($row, $actor, $payload));
    }

    private function reject(Correspondence $row, User $actor, array $payload): void
    {
        CorrespondenceAccessPolicy::canView($actor, $row) || LetterRules::deny();

        $parties = LetterFlow::parties($row);
        $me = (int) $actor->id;
        if (LetterFlow::approverIndex($parties, $me) === null) {
            LetterRules::deny();
        }
        if ($row->status !== Correspondence::STATUS_SHARED
            || ($row->route_mode !== Correspondence::ROUTE_PARALLEL && (int) $row->awaiting_user_id !== $me)) {
            throw LetterRules::fail('status', 'not_awaiting');
        }

        $reason = LetterRules::plain($payload['reason'] ?? '', 500);
        if ($reason === '') {
            throw LetterRules::fail('rejectReason', 'reason_required');
        }
        LetterFlow::expect($row, $payload);

        $resume = filter_var($payload['resume'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $meta = (array) ($row->meta ?? []);
        unset($meta['resume']);
        if ($resume) {
            $meta['resume'] = ['user_id' => $me, 'hash' => LetterRules::contentHash($row)];
        }

        if ($resume) {
            unset($parties[LetterFlow::approverIndex($parties, $me)]['signed_at']);
        }

        $row->forceFill([
            'parties' => $resume ? $parties : LetterFlow::withoutSignatures($parties),
            'status' => Correspondence::STATUS_REJECTED,
            'awaiting_user_id' => null,
            'meta' => $meta ?: null,
        ]);
        LetterFlow::writeSteps($row);
        LetterFlow::log($row, $actor, 'rejected', ['reason' => $reason]);
        $row->save();
    }
}
