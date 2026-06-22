<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class BulkMoveTasksAction
{
    public function execute(array $taskIds, string $status): int
    {
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_change_status);

        $tasks->each(fn(Task $task) => $task->update(['status' => $status]));

        return $tasks->count();
    }
}
