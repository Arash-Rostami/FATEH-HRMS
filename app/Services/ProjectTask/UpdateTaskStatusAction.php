<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatusAction
{
    public function execute(int $taskId, string $status): bool
    {
        if (!TaskStatus::tryFrom($status)) {
            return false;
        }

        $task = Task::query()->with('detail:id,task_id,state')->find($taskId);

        if (!$task?->can_change_status) {
            return false;
        }

        if ($status === TaskStatus::Done->value && empty($task->detail?->state)) {
            return false;
        }

        if ($task->status === $status) {
            return true;
        }

        DB::transaction(function () use ($task, $status) {
            $ownerId = $task->assigned_to ?? $task->user_id;

            $task->update([
                'status' => $status,
                'rank' => Task::nextRank($task->project_id, $ownerId, $status),
            ]);
        });

        return true;
    }
}
