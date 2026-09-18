<?php

namespace App\Livewire\Dashboard\Reminder\Actions;

use App\Models\Reminder;
use Carbon\Carbon;

class SnoozeReminderAction
{
    public function execute(Reminder $reminder, int $userId, Carbon $until): bool
    {
        abort_if($reminder->user_id !== $userId, 403);

        $reminder->snooze($until);

        return true;
    }
}
