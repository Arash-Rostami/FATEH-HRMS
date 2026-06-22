<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;

class AssignTaskAction
{
    public function execute(int $taskId, ?int $userId): ?Task
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status) {
            return null;
        }

        $task->update(['assigned_to' => $userId]);

        if ($userId && $userId !== auth()->id() && $assignee = User::find($userId)) {
            Notification::make()
                ->title('وظیفه جدید به شما محول شد')
                ->body("وظیفه «{$task->title}» توسط " . auth()->user()->name . " به شما ارجاع شد.")
                ->success()
                ->sendToDatabase($assignee);
        }

        return $task;
    }
}
