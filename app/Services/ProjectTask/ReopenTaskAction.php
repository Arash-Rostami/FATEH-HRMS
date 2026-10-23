<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ReopenTaskAction
{
    public function execute(Task $task, ?User $actor): bool
    {
        if ($actor === null || !$task->can_reopen || $task->cycleStep() !== null) {
            return false;
        }

        DB::transaction(function () use ($task, $actor) {
            $status = TaskStatus::InProgress->value;

            $task->update([
                'status' => $status,
                'rank' => Task::nextRank($task->project_id, $task->assigned_to ?? $task->user_id, $status),
            ]);

            app(ActivityLogger::class)->system($task, $actor, TaskActivityType::Approval, ['approved' => false]);
        });

        return true;
    }
}
