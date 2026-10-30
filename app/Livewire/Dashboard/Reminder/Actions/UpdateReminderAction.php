<?php

namespace App\Livewire\Dashboard\Reminder\Actions;

use App\Livewire\Dashboard\Reminder\Forms\ReminderForm;
use App\Models\Reminder;
use App\Traits\ResolvesReminderDueDate;

class UpdateReminderAction
{
    use ResolvesReminderDueDate;

    public function execute(Reminder $reminder, ReminderForm $form, int $userId): bool
    {
        abort_if($reminder->user_id !== $userId, 403);

        $form->validate();

        $dueAt = $this->resolveDueAt($form);

        return $reminder->update([
            'title' => trim($form->title),
            'notes' => $form->notes !== '' ? trim($form->notes) : null,
            'due_at' => $dueAt,
            'recurs' => $form->recurs,
            'channels' => $form->toChannels(),
        ] + ($dueAt?->eq($reminder->due_at) ? [] : ['notified_at' => null]));
    }
}
