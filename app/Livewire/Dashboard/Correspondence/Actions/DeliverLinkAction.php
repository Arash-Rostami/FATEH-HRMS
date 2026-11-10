<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Jobs\SendCorrespondenceJob;
use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class DeliverLinkAction
{
    public function execute(int $id, int $index, string $action): void
    {
        $actor = auth()->user();
        if (!$actor instanceof User || !$actor->isActive() || !in_array($action, ['send', 'remind', 'revoke'], true)) {
            LetterRules::deny();
        }

        correspondenceMailOn() || throw LetterRules::fail('form', 'mail_off');

        $key = "correspondence-link:{$actor->id}:{$id}";
        if (RateLimiter::hit($key, 60) > LetterFlow::LINK_ATTEMPTS) {
            throw LetterRules::fail('form', 'too_many_attempts');
        }

        DB::transaction(function () use ($id, $index, $action, $actor) {
            $row = Correspondence::query()->whereKey($id)->lockForUpdate()->first() ?? LetterRules::deny();
            CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
            if ($row->status !== Correspondence::STATUS_APPROVED || $row->direction !== Correspondence::DIRECTION_OUT) {
                throw LetterRules::fail('status', 'invalid_state');
            }

            $party = ((array) ($row->parties ?? []))[$index] ?? null;
            if (!is_array($party) || filled($party['user_id'] ?? null)) {
                LetterRules::deny();
            }

            $sent = filled($party['sent_at'] ?? null);
            if ($action === 'revoke') {
                $sent && LetterRules::linked($party) && !SendCorrespondenceJob::sendingFresh($party) || throw LetterRules::fail('status', 'invalid_state');
                SendCorrespondenceJob::revoke($row, $index, (int) $actor->id);

                return;
            }
            if (!LetterRules::modeValid($party)) {
                throw LetterRules::fail('form', 'invalid_value');
            }
            if ($row->classification !== Correspondence::CLASSIFICATION_NORMAL) {
                throw LetterRules::fail('status', 'link_refused');
            }
            if ($row->sign_mode === Correspondence::SIGN_MODE_WET && !LetterFlow::hasEvent($row, 'wet_signed')) {
                throw LetterRules::fail('status', 'link_wet_pending');
            }
            if (!LetterRules::emailValid($party)) {
                throw LetterRules::fail('form', 'invalid_email');
            }
            if ($action === 'send' ? $sent : (!$sent || (filled($party['opened_at'] ?? null) && !LetterRules::lapsed($party)))) {
                throw LetterRules::fail('status', 'invalid_state');
            }

            SendCorrespondenceJob::dispatch((int) $row->id, [$index], $action === 'remind', (int) $actor->id);
        });
    }
}
