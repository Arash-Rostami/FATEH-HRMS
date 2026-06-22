<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class BulkDeleteTasksAction
{
    public function execute(array $taskIds): int
    {
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_delete);

        $tasks->each(fn(Task $task) => $task->delete());

        return $tasks->count();
    }
}
