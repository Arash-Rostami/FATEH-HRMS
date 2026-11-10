<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class CommentLetterAction
{
    private const MAX_COMMENTS = 500;

    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->comment($row, $actor, $payload));
    }

    private function comment(Correspondence $row, User $actor, array $payload): void
    {
        CorrespondenceAccessPolicy::canView($actor, $row) || LetterRules::deny();
        if (!in_array($row->status, Correspondence::COMMENTABLE, true)) {
            throw LetterRules::fail('status', 'invalid_state');
        }

        $text = LetterRules::plain($payload['text'] ?? '', 1000);
        if ($text === '') {
            throw LetterRules::fail('commentText', 'text_required');
        }
        $comments = count(array_filter((array) ($row->events ?? []), fn ($event) => is_array($event) && ($event['action'] ?? null) === 'commented'));
        if ($comments >= self::MAX_COMMENTS) {
            throw LetterRules::fail('commentText', 'too_many_comments');
        }

        LetterFlow::log($row, $actor, 'commented', ['text' => $text]);
        $row->save();
    }
}
