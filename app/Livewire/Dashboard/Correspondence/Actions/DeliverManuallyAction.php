<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class DeliverManuallyAction
{
    private const MAX_DELIVERIES = 200;

    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->deliver($row, $actor, $payload));
    }

    private function deliver(Correspondence $row, User $actor, array $payload): void
    {
        if (!CorrespondenceAccessPolicy::canSteward($actor, $row) || $row->direction !== Correspondence::DIRECTION_OUT) {
            LetterRules::deny();
        }
        if ($row->status !== Correspondence::STATUS_APPROVED) {
            throw LetterRules::fail('status', 'invalid_state');
        }

        $text = LetterRules::plain($payload['text'] ?? '', 500);
        if ($text === '') {
            throw LetterRules::fail('deliveryNote', 'text_required');
        }
        $delivered = count(array_filter((array) ($row->events ?? []), fn ($event) => is_array($event) && ($event['action'] ?? null) === 'delivered_manually'));
        if ($delivered >= self::MAX_DELIVERIES) {
            throw LetterRules::fail('deliveryNote', 'too_many_events');
        }

        $extra = ['text' => $text];
        $party = $payload['party'] ?? null;

        if ($party !== null) {
            $index = is_int($party) ? $party : (is_string($party) && ctype_digit($party) ? (int) $party : -1);
            $parties = (array) ($row->parties ?? []);
            $entry = $parties[$index] ?? null;

            if (!is_array($entry) || filled($entry['user_id'] ?? null)) {
                LetterRules::deny();
            }

            $parties[$index]['sent_at'] = filled($entry['sent_at'] ?? null) ? $entry['sent_at'] : now()->toIso8601String();
            $parties[$index]['manual_note'] = $text;
            $row->parties = $parties;
            $extra['target'] = LetterRules::plain(($entry['name'] ?? '') ?: (($entry['org'] ?? '') ?: ($entry['email'] ?? '')), 200);
        }

        LetterFlow::log($row, $actor, 'delivered_manually', $extra);
        $row->save();
    }
}
