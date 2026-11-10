<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\User;

class RouteLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->route($row, $actor, $payload));
    }

    private function route(Correspondence $row, User $actor, array $payload): void
    {
        LetterFlow::incomingRegistry($row, $actor, [Correspondence::STATUS_RECEIVED, Correspondence::STATUS_ROUTED]);

        $text = LetterRules::plain($payload['instruction'] ?? '', 500);
        $parties = LetterFlow::parties($row);
        $have = array_map(fn (array $party) => (int) ($party['user_id'] ?? 0), $parties);
        $active = User::getCachedActiveOptions();
        $wanted = [];

        foreach (is_array($payload['parties'] ?? null) ? $payload['parties'] : [] as $party) {
            $userId = is_array($party) && is_numeric($party['user_id'] ?? null) ? (int) $party['user_id'] : 0;
            if ($userId > 0 && $active->has($userId) && !in_array($userId, $have, true)) {
                $wanted[$userId] = $userId;
            }
        }

        if ($wanted === []) {
            throw LetterRules::fail('routeParties', 'no_parties');
        }
        if (count($parties) + count($wanted) > (int) config('ai.correspondence.max_recipients')) {
            throw LetterRules::fail('routeParties', 'too_many_recipients');
        }

        foreach ($wanted as $userId) {
            $parties[] = [...LetterForm::blankParty(), 'user_id' => $userId, 'role' => LetterForm::ROLE_FYI, 'name' => (string) $active->get($userId), 'order' => count($parties) + 1];
            LetterFlow::log($row, $actor, 'routed', ['target_id' => $userId, 'text' => $text]);
        }

        $row->forceFill(['parties' => $parties, 'status' => Correspondence::STATUS_ROUTED]);
        $row->recipients = LetterRules::recipientIds($parties);
        $row->content_hash = LetterRules::contentHash($row);
        $row->save();
    }
}
