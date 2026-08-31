<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Services\Menu\StateService;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class BulkAssignTasksAction
{
    public function execute(array $taskIds, ?int $userId): int
    {
        $user = auth()->user();
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => TaskAccessPolicy::canChangeStatus($task, $user) && !$task->ticket_id);

        if ($tasks->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($tasks, $userId) {
            foreach ($tasks as $task) {
                $task->update(['assigned_to' => $userId]);
            }
        });

        DB::afterCommit(fn() => StateService::flush());

        return $tasks->count();
    }
}
