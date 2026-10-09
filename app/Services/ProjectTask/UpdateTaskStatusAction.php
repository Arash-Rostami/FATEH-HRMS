<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskStatusAction
{
    public function execute(int $taskId, string $status, ?string $version = null): ?bool
    {
        if (!TaskStatus::tryFrom($status)) {
            return false;
        }

        $task = Task::query()->with('project:id,owner_id', 'detail:id,task_id,state,collaborators,responsible_user_id')->find($taskId);

        if (!$task?->can_change_status || $task->ticket_id) {
            return false;
        }

        if ($version !== null && $task->version !== $version) {
            return null;
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
