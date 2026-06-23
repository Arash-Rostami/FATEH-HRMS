<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;

class BulkMoveTasksAction
{
    public function execute(array $taskIds, string $status): int
    {
        if (!TaskStatus::tryFrom($status)) {
            return 0;
        }

        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_change_status);

        $tasks->each(fn(Task $task) => $task->update(['status' => $status]));

        return $tasks->count();
    }
}
