<?php

namespace App\Livewire\Dashboard\Reminder\Actions;

use App\Enums\ReminderRecurrence;
use App\Models\Reminder;

class StopRecurrenceAction
{
    public function execute(Reminder $reminder, int $userId): bool
    {
        abort_if($reminder->user_id !== $userId, 403);

        return $reminder->update(['recurs' => ReminderRecurrence::None]);
    }
}
