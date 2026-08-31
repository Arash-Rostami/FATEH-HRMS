<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskAccessPolicy;

class UndoTaskAssignmentAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canUndoAssignment($task, auth()->user()) || $task->status === TaskStatus::Done->value || $task->ticket_id) {
            return false;
        }

        $task->update(['assigned_to' => null]);

        return true;
    }
}
