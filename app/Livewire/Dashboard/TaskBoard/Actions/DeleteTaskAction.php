<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class DeleteTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task?->can_delete) {
            return false;
        }

        $task->delete();

        return true;
    }
}
