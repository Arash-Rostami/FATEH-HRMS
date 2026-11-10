<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class ReceiveLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->receive($row, $actor));
    }

    private function receive(Correspondence $row, User $actor): void
    {
        if (!CorrespondenceAccessPolicy::isRegistry($actor) || !CorrespondenceAccessPolicy::isAuthor($actor, $row) || $row->direction !== Correspondence::DIRECTION_IN) {
            LetterRules::deny();
        }
        if (!in_array($row->status, [Correspondence::STATUS_DRAFT, Correspondence::STATUS_SAVED], true)) {
            throw LetterRules::fail('status', 'invalid_state');
        }
        if (trim((string) $row->subject) === '') {
            throw LetterRules::fail('form.subject', 'subject_required');
        }
        $head = LetterFlow::requireLetterhead($row, Letterhead::KIND_REGISTRY);

        $moment = now('Asia/Tehran');
        $number = $head->nextNumber(LetterFlow::numberContext($row, $moment));

        $parties = LetterFlow::parties($row);
        if ($row->sender_category === Correspondence::CATEGORY_AUTHORITY && config('ai.correspondence.authority_cc_ceo')) {
            $parties = LetterFlow::withCeo($parties, (int) $row->user_id);
        }

        $row->forceFill([
            'parties' => $parties,
            'number' => $number['number'],
            'number_seq' => $number['seq'],
            'letter_date' => filled($row->their_date) ? $row->their_date : LetterFlow::jalali($moment),
            'received_at' => now(),
            'status' => Correspondence::STATUS_RECEIVED,
            'awaiting_user_id' => null,
        ]);
        $row->recipients = LetterRules::recipientIds($parties);
        $row->content_hash = LetterRules::contentHash($row);
        LetterFlow::log($row, $actor, 'registered');
        $row->save();
    }
}
