<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Menu\StateService;
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
        $tasks = Task::whereIn('id', $taskIds)->with(['detail:id,task_id,state', 'project:id,settings,owner_id'])->get()->filter(fn(Task $task) => TaskAccessPolicy::canChangeStatus($task, $user));

        $skipped = 0;
        if ($status === TaskStatus::Done->value) {
            [$tasks, $blocked] = $tasks->partition(fn(Task $task) => !empty($task->detail?->state));
            $skipped = $blocked->count();
        }

        if ($tasks->isEmpty()) {
            return ['moved' => 0, 'skipped' => $skipped];
        }

        $payload = ['status' => $status];
        if ($status !== TaskStatus::Done->value) {
            $payload['archived_at'] = null;
        }

        DB::transaction(function () use ($tasks, $payload) {
            foreach ($tasks as $task) {
                $task->update($payload);
            }
        });

        DB::afterCommit(fn() => StateService::flush());

        return ['moved' => $tasks->count(), 'skipped' => $skipped];
    }
}
