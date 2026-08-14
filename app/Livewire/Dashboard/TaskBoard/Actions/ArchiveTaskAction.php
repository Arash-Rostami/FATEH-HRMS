<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class ArchiveTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task?->can_delete || $task->ticket_id) {
            return false;
        }

        if ($task->status !== 'done' || $task->archived_at !== null) {
            return false;
        }

        $task->update(['archived_at' => now()]);

        return true;
    }
}