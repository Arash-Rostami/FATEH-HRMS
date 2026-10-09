<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Menu\StateService;
use App\Services\ProjectTask\RankGenerator;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class BulkMoveTasksAction
{
    public function execute(array $taskIds, string $status): array
    {
        if (!TaskStatus::tryFrom($status)) {
            return ['moved' => 0, 'skipped' => 0];
        }

        $user = auth()->user();
        $tasks = Task::whereIn('id', $taskIds)
            ->whereNull('archived_at')
            ->whereNull('ticket_id')
            ->where('status', '!=', $status)
            ->with(['detail:id,task_id,state,collaborators,responsible_user_id', 'project:id,settings,owner_id'])
            ->get()
            ->filter(fn(Task $task) => TaskAccessPolicy::canChangeStatus($task, $user));

        $skipped = 0;
        if ($status === TaskStatus::Done->value) {
            [$tasks, $blocked] = $tasks->partition(fn(Task $task) => !empty($task->detail?->state));
            $skipped = $blocked->count();
        }

        if ($tasks->isEmpty()) {
            return ['moved' => 0, 'skipped' => $skipped];
        }

        DB::transaction(function () use ($tasks, $status) {
            $ranks = [];

            foreach ($tasks as $task) {
                $ownerId = $task->assigned_to ?? $task->user_id;
                $key = $task->project_id ?: 'u' . $ownerId;

                $ranks[$key] = isset($ranks[$key])
                    ? RankGenerator::between(null, $ranks[$key])
                    : Task::nextRank($task->project_id, $ownerId, $status);

                $task->update(['status' => $status, 'rank' => $ranks[$key]]);
            }
        });

        DB::afterCommit(fn() => StateService::flush());

        return ['moved' => $tasks->count(), 'skipped' => $skipped];
    }
}
