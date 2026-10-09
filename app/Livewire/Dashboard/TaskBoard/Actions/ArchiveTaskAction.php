<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class ArchiveTaskAction
{
    public function execute(int $taskId): bool
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canArchive($task, auth()->user())) {
            return false;
        }

        if ($task->archived_at !== null) {
            return false;
        }

        DB::transaction(fn() => $task->update(['archived_at' => now()]));

        return true;
    }
}