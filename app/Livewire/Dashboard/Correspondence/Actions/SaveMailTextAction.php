<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\RateLimiter;

class SaveMailTextAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->mailText($row, $actor, $payload));
    }

    private function mailText(Correspondence $row, User $actor, array $payload): void
    {
        CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
        correspondenceMailOn() || throw LetterRules::fail('form', 'mail_off');
        if (RateLimiter::hit("correspondence-mail:{$actor->id}:{$row->id}", 60) > LetterFlow::LINK_ATTEMPTS) {
            throw LetterRules::fail('form', 'too_many_attempts');
        }
        $external = array_filter(LetterFlow::parties($row), fn (array $party) => blank($party['user_id'] ?? null));
        if ($row->status !== Correspondence::STATUS_APPROVED || $row->direction !== Correspondence::DIRECTION_OUT
            || $row->classification !== Correspondence::CLASSIFICATION_NORMAL || $external === []) {
            throw LetterRules::fail('status', 'invalid_state');
        }

        $body = preg_replace(["/\r\n?/", "/\n{3,}/"], ["\n", "\n\n"], LetterRules::plain($payload['body'] ?? '', LetterRules::MAIL_BODY_MAX));
        $mail = array_filter([
            'subject' => trim((string) preg_replace('/\s+/u', ' ', LetterRules::plain($payload['subject'] ?? '', LetterRules::MAIL_SUBJECT_MAX))),
            'body' => trim((string) $body),
        ], fn (string $value) => $value !== '');

        $meta = (array) ($row->meta ?? []);
        unset($meta['mail']);
        if ($mail !== []) {
            $meta['mail'] = $mail;
        }
        $row->forceFill(['meta' => $meta ?: null]);
        $row->save();
    }
}
