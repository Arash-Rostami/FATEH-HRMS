<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LetterParties
{
    private const PARTY_STATE = ['signed_at', 'sent_at', 'opened_at', 'comment'];

    public static function clean(array $incoming, array $existing, string $field = 'form.parties'): array
    {
        $state = [];
        foreach ($existing as $party) {
            if (is_array($party) && filled($party['user_id'] ?? null)) {
                $state[(int) $party['user_id']] = array_intersect_key($party, array_flip(self::PARTY_STATE));
            }
        }

        $active = User::getCachedActiveOptions();
        $approvers = [];
        $readers = [];
        $seen = [];
        foreach ($incoming as $index => $party) {
            $one = is_array($party) ? self::one($party, $active, "{$field}.{$index}.email") : null;
            if ($one === null) {
                continue;
            }
            if ($one['user_id'] !== null) {
                if (isset($seen[$one['user_id']])) {
                    continue;
                }
                $seen[$one['user_id']] = true;
                $one += $state[$one['user_id']] ?? [];
            }
            if ($one['role'] === LetterForm::ROLE_APPROVER) {
                $approvers[] = $one;
            } else {
                $readers[] = $one;
            }
        }

        $all = [...$approvers, ...$readers];
        if (count($all) > (int) config('ai.correspondence.max_recipients')) {
            throw ValidationException::withMessages([$field => __('resources/correspondence/strings.errors.too_many_recipients')]);
        }

        return array_map(fn (array $party, int $i) => [...$party, 'order' => $i + 1], $all, array_keys($all));
    }

    public static function withoutActs(array $parties): array
    {
        return array_map(function (array $party) {
            unset($party['signed_at']);

            return $party;
        }, array_values(array_filter($parties, 'is_array')));
    }

    private static function one(array $party, Collection $active, string $emailField): ?array
    {
        $userId = is_numeric($party['user_id'] ?? null) ? (int) $party['user_id'] : null;
        $print = filter_var($party['print_in_cc'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $one = LetterForm::blankParty();

        if ($userId !== null) {
            if (!$active->has($userId)) {
                return null;
            }
            $role = ($party['role'] ?? null) === LetterForm::ROLE_APPROVER ? LetterForm::ROLE_APPROVER : LetterForm::ROLE_FYI;

            return [...$one, 'user_id' => $userId, 'role' => $role, 'print_in_cc' => $role === LetterForm::ROLE_FYI && $print, 'name' => (string) $active->get($userId)];
        }

        $email = trim((string) ($party['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([$emailField => __('resources/correspondence/strings.errors.invalid_email')]);
        }

        $name = LetterRules::plain($party['name'] ?? '', 200);
        if ($name === '' && $email === '') {
            return null;
        }

        return [
            ...$one,
            'print_in_cc' => $print,
            'name' => $name,
            'org' => LetterRules::plain($party['org'] ?? '', 200),
            'title' => LetterRules::plain($party['title'] ?? '', 200),
            'email' => $email,
            'address' => LetterRules::plain($party['address'] ?? '', 300),
            'delivery' => LetterRules::oneOf($party['delivery'] ?? null, LetterForm::DELIVERIES, 'link'),
        ];
    }
}
