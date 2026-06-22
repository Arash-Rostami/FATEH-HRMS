<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class BulkAssignTasksAction
{
    public function execute(array $taskIds, ?int $userId): int
    {
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_change_status);

        $tasks->each(fn(Task $task) => $task->update(['assigned_to' => $userId]));

        return $tasks->count();
    }
}
