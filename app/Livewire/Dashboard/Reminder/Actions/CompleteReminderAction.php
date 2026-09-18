<?php

namespace App\Livewire\Dashboard\Reminder\Actions;

use App\Models\Reminder;

class CompleteReminderAction
{
    public function execute(Reminder $reminder, int $userId): bool
    {
        abort_if($reminder->user_id !== $userId, 403);

        return $reminder->complete();
    }
}
