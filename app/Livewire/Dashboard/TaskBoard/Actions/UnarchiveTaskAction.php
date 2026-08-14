<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class UnarchiveTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task?->can_delete || $task->archived_at === null) {
            return false;
        }

        $task->update(['archived_at' => null]);

        return true;
    }
}