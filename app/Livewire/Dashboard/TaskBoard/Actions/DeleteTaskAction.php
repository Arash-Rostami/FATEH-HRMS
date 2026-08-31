<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Support\TaskAccessPolicy;

class DeleteTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canDelete($task, auth()->user()) || $task->ticket_id) {
            return false;
        }

        $task->delete();

        return true;
    }
}
