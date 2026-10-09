<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class AssignTaskAction
{
    public function execute(int $taskId, ?int $userId): ?Task
    {
        $task = Task::find($taskId);

        if (!$task || !TaskAccessPolicy::canReassign($task, auth()->user(), $userId) || $task->ticket_id) {
            return null;
        }

        if ($userId && TaskAccessPolicy::assignableIds([$userId], $task->project) === []) {
            return null;
        }

        DB::transaction(fn() => $task->update(['assigned_to' => $userId]));

        return $task;
    }
}
