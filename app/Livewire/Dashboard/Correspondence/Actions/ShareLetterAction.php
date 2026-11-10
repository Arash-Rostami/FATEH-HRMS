<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class ShareLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->share($row, $actor, $payload));
    }

    private function share(Correspondence $row, User $actor, array $payload): void
    {
        if (!CorrespondenceAccessPolicy::isAuthor($actor, $row) || $row->direction !== Correspondence::DIRECTION_OUT) {
            LetterRules::deny();
        }
        if (!in_array($row->status, [Correspondence::STATUS_DRAFT, Correspondence::STATUS_SAVED, Correspondence::STATUS_REJECTED], true)) {
            throw LetterRules::fail('status', 'invalid_state');
        }
        LetterFlow::expect($row, $payload);

        $parties = LetterFlow::parties($row);
        if ($parties === []) {
            throw LetterRules::fail('form', 'no_parties');
        }
        if (LetterFlow::approverCount($parties) === 0) {
            throw LetterRules::fail('form', 'route_incomplete');
        }
        if (trim((string) $row->subject) === '') {
            throw LetterRules::fail('form.subject', 'subject_required');
        }
        if (!LetterFlow::hasBody($row)) {
            throw LetterRules::fail('form', 'empty_body');
        }
        if ($row->deadline_at !== null && now()->gt($row->deadline_at)) {
            throw LetterRules::fail('form', 'deadline_past');
        }
        if (LetterFlow::presenter()->hasUnfilled($row)) {
            throw LetterRules::fail('form', 'placeholders');
        }
        LetterFlow::requireLetterhead($row, Letterhead::KIND_HEAD);
        LetterFlow::requireRoute($parties);
        if ($row->sign_mode === Correspondence::SIGN_MODE_ELECTRONIC) {
            LetterSeal::signatureSources($parties);
        }

        $meta = (array) ($row->meta ?? []);
        $resumed = $row->status === Correspondence::STATUS_REJECTED
            && is_array($meta['resume'] ?? null)
            && hash_equals((string) ($meta['resume']['hash'] ?? ''), LetterRules::contentHash($row));
        unset($meta['resume']);

        $row->forceFill([
            'parties' => $resumed ? $parties : LetterFlow::withoutSignatures($parties),
            'status' => Correspondence::STATUS_SHARED,
            'shared_at' => now(),
            'meta' => $meta ?: null,
        ]);
        LetterFlow::writeSteps($row);
        LetterFlow::log($row, $actor, 'shared');
        $row->save();
    }
}
