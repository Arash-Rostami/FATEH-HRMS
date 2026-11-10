<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RenameLetterAction
{
    public function execute(User $user, array $changes, Correspondence $letter): Correspondence
    {
        return LetterWrite::run($user, $changes, $letter, fn (Correspondence $fresh) => $this->rename($fresh, $user, (string) $changes['subject']));
    }

    private function rename(Correspondence $letter, User $user, string $subject): void
    {
        $before = LetterRules::contentHash($letter);
        $letter->subject = LetterRules::plain($subject, 200);
        if ($letter->subject === '') {
            throw ValidationException::withMessages(['form.subject' => __('resources/correspondence/strings.errors.subject_required')]);
        }

        LetterWrite::commit($letter, $user, $before);
    }
}
