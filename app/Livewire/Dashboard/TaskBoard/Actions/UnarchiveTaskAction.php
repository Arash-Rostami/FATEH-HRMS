<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Support\TaskAccessPolicy;

class UnarchiveTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canDelete($task, auth()->user()) || $task->archived_at === null) {
            return false;
        }

        $task->update(['archived_at' => null]);

        return true;
    }
}