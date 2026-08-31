<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskAccessPolicy;

class ArchiveTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canDelete($task, auth()->user()) || $task->ticket_id) {
            return false;
        }

        if ($task->status !== TaskStatus::Done->value || $task->archived_at !== null) {
            return false;
        }

        $task->update(['archived_at' => now()]);

        return true;
    }
}