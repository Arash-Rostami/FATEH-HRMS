<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;

class RerouteLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->reroute($row, $actor, $payload));
    }

    private function reroute(Correspondence $row, User $actor, array $payload): void
    {
        CorrespondenceAccessPolicy::isAuthor($actor, $row) || LetterRules::deny();
        if ($row->status !== Correspondence::STATUS_SHARED) {
            throw LetterRules::fail('status', 'not_awaiting');
        }
        LetterFlow::expect($row, $payload);

        $existing = LetterFlow::parties($row);
        $cleaned = LetterParties::clean(is_array($payload['parties'] ?? null) ? $payload['parties'] : [], $existing, 'routeParties');

        $signed = [];
        foreach ($existing as $party) {
            if (($party['role'] ?? null) === LetterForm::ROLE_APPROVER && filled($party['signed_at'] ?? null) && filled($party['user_id'] ?? null)) {
                $signed[(int) $party['user_id']] = $party;
            }
        }

        $fresh = array_values(array_filter($cleaned, fn (array $party) => !isset($signed[(int) ($party['user_id'] ?? 0)])));
        $approvers = [...array_values($signed), ...array_values(array_filter($fresh, fn (array $party) => $party['role'] === LetterForm::ROLE_APPROVER))];
        $readers = array_values(array_filter($fresh, fn (array $party) => $party['role'] !== LetterForm::ROLE_APPROVER));
        $all = [...$approvers, ...$readers];

        if ($all === []) {
            throw LetterRules::fail('routeParties', 'no_parties');
        }
        if ($approvers === [] || LetterFlow::unsignedApprovers($approvers) === 0) {
            throw LetterRules::fail('form', 'route_incomplete');
        }
        if (count($all) > (int) config('ai.correspondence.max_recipients')) {
            throw LetterRules::fail('routeParties', 'too_many_recipients');
        }

        $mode = $payload['route_mode'] ?? null;
        $meta = (array) ($row->meta ?? []);
        unset($meta['resume']);

        $row->forceFill([
            'parties' => array_map(fn (array $party, int $i) => [...$party, 'order' => $i + 1], $all, array_keys($all)),
            'route_mode' => in_array($mode, LetterForm::ROUTES, true) ? $mode : $row->route_mode,
            'meta' => $meta ?: null,
        ]);
        $row->recipients = LetterRules::recipientIds($row->parties);
        LetterFlow::writeSteps($row);
        $row->content_hash = LetterRules::contentHash($row);
        LetterFlow::log($row, $actor, 'rerouted');
        $row->save();
    }
}
