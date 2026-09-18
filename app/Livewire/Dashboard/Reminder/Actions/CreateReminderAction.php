<?php

namespace App\Livewire\Dashboard\Reminder\Actions;

use App\Livewire\Dashboard\Reminder\Forms\ReminderForm;
use App\Models\Reminder;
use App\Traits\ResolvesReminderDueDate;

class CreateReminderAction
{
    use ResolvesReminderDueDate;

    public function execute(ReminderForm $form, int $userId, ?string $remindableType, ?int $remindableId): Reminder
    {
        $form->validate();

        return Reminder::create([
            'user_id' => $userId,
            'remindable_type' => $remindableType,
            'remindable_id' => $remindableId,
            'title' => trim($form->title),
            'notes' => $form->notes !== '' ? trim($form->notes) : null,
            'due_at' => $this->resolveDueAt($form),
            'recurs' => $form->recurs,
            'channels' => $form->toChannels(),
        ]);
    }
}
