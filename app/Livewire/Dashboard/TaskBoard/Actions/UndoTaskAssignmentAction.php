<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class UndoTaskAssignmentAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task?->is_delegator || $task->status === 'done') {
            return false;
        }

        $task->update(['assigned_to' => null]);

        return true;
    }
}
