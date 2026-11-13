<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Models\Correspondence;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;

class CorrectLetterAction
{
    public function execute(int $id, array $payload = []): void
    {
        LetterFlow::run($id, fn (Correspondence $row, User $actor) => $this->correct($row, $actor, $payload));
    }

    private function correct(Correspondence $row, User $actor, array $payload): void
    {
        LetterFlow::incomingRegistry($row, $actor, [Correspondence::STATUS_RECEIVED]);
        LetterFlow::expect($row, $payload);

        $had = filled($row->getRawOriginal('their_date'));
        $this->fix($row, is_array($payload['fields'] ?? null) ? $payload['fields'] : []);

        if (filled($row->their_date)) {
            $row->letter_date = $row->their_date;
        } elseif ($had) {
            $row->letter_date = LetterFlow::jalali(now('Asia/Tehran'));
        }

        LetterFlow::log($row, $actor, 'corrected');
        $row->save();
        $row->forceFill(['content_hash' => LetterRules::contentHash($row)])->save();
    }

    private function fix(Correspondence $letter, array $fields): void
    {
        $subject = LetterRules::plain($fields['subject'] ?? '', 200);
        if ($subject === '') {
            throw ValidationException::withMessages(['correction.subject' => __('resources/correspondence/strings.errors.subject_required')]);
        }

        $date = is_scalar($fields['their_date'] ?? null) ? CalendarUtils::convertNumbers((string) $fields['their_date'], true) : '';
        $spec = is_array($letter->spec) ? $letter->spec : [];

        $letter->subject = $subject;
        $letter->their_number = LetterRules::plain($fields['their_number'] ?? '', 64);
        $letter->their_date = LetterRules::checkedDate($date, 'theirDate');
        $spec['sender'] = LetterBody::senderBlock($fields['sender'] ?? '', is_array($spec['sender'] ?? null) ? $spec['sender'] : []);
        $letter->spec = $spec;
    }
}
