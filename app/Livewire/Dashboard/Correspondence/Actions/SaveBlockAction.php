<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SaveBlockAction
{
    public function execute(User $user, array $changes, Correspondence $letter): Correspondence
    {
        return LetterWrite::run($user, $changes, $letter, fn (Correspondence $fresh) => $this->block($fresh, $user, $changes));
    }

    private function block(Correspondence $letter, User $user, array $changes): void
    {
        $index = (int) $changes['block'];
        $blocks = $letter->spec['blocks'] ?? [];
        $original = $blocks[$index] ?? null;
        if (!is_array($original)) {
            LetterRules::deny();
        }
        if (is_string($changes['block_hash'] ?? null) && !hash_equals(LetterRules::blockHash($original), $changes['block_hash'])) {
            throw LetterRules::stale();
        }

        $clean = LetterBody::oneBlock(is_array($changes['data'] ?? null) ? $changes['data'] : [], LetterBody::knownPaths($letter), (int) $letter->id);
        if ($clean === null) {
            throw ValidationException::withMessages(['blockDraft' => __('resources/correspondence/strings.errors.block_incomplete')]);
        }

        $before = LetterRules::contentHash($letter);
        $blocks[$index] = $clean;
        $spec = $letter->spec ?? [];
        $spec['blocks'] = $blocks;
        $letter->spec = $spec;
        LetterWrite::commit($letter, $user, $before);
    }
}
