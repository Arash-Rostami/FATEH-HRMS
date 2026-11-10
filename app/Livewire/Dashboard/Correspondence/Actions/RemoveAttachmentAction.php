<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;

class RemoveAttachmentAction
{
    public function execute(User $user, array $changes, Correspondence $letter): Correspondence
    {
        return LetterWrite::run($user, $changes, $letter, fn (Correspondence $fresh) => $this->remove($fresh, $user, (int) $changes['remove_attachment']));
    }

    private function remove(Correspondence $letter, User $user, int $index): void
    {
        $attachments = $letter->spec['attachments'] ?? [];
        if (!isset($attachments[$index])) {
            LetterRules::deny();
        }

        $known = LetterBody::knownPaths($letter);
        $before = LetterRules::contentHash($letter);
        unset($attachments[$index]);
        $spec = $letter->spec ?? [];
        $spec['attachments'] = array_values($attachments);
        $letter->spec = $spec;
        LetterWrite::commit($letter, $user, $before);
        LetterBody::forgetUnused($letter, $known);
    }
}
