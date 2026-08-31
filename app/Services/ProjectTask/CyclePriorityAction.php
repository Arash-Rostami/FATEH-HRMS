<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CyclePriorityAction
{
    private const ORDER = [TaskPriority::Low, TaskPriority::Medium, TaskPriority::High, TaskPriority::Urgent];

    public function execute(int $taskId): ?Task
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status || $task->ticket_id || $task->is_archived) {
            return null;
        }

        DB::transaction(function () use ($task) {
            $currentIndex = array_search($task->priority, self::ORDER, true);
            $next = self::ORDER[$currentIndex === false ? 0 : ($currentIndex + 1) % count(self::ORDER)];
            $ownerId = $task->assigned_to ?? $task->user_id;

            $task->update([
                'priority' => $next,
                'rank' => Task::rankForPriority($task->project_id, $ownerId, $task->status, $next->value, $task->id),
            ]);
        });

        return $task;
    }
}
