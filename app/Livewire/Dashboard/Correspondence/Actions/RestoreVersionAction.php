<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;

class RestoreVersionAction
{
    public function execute(User $user, array $changes, Correspondence $letter): Correspondence
    {
        return LetterWrite::run($user, $changes, $letter, fn (Correspondence $fresh) => $this->restore($fresh, $user, (int) $changes['restore']));
    }

    private function restore(Correspondence $letter, User $user, int $index): void
    {
        $version = $letter->versions[$index] ?? null;
        $spec = is_array($version) ? ($version['spec'] ?? null) : null;
        if (!is_array($spec)) {
            LetterRules::deny();
        }

        $before = LetterRules::contentHash($letter);
        $letter->spec = LetterBody::liveSpec($spec);
        $letter->subject = is_string($version['subject'] ?? null) ? LetterRules::plain($version['subject'], 200) : $letter->subject;
        LetterWrite::commit($letter, $user, $before);
    }
}
