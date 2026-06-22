<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class UpdateTaskStatusAction
{
    public function execute(int $taskId, string $status): bool
    {
        $task = Task::find($taskId);

        if (!$task?->can_change_status) {
            return false;
        }

        $task->update(['status' => $status]);

        return true;
    }
}
