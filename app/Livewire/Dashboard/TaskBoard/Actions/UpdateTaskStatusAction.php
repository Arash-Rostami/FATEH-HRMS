<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;

class UpdateTaskStatusAction
{
    public function execute(int $taskId, string $status): bool
    {
        if (!TaskStatus::tryFrom($status)) {
            return false;
        }

        $task = Task::find($taskId);

        if (!$task?->can_change_status) {
            return false;
        }

        $task->update(['status' => $status]);

        return true;
    }
}
