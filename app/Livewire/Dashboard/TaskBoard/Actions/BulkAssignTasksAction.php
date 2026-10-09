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
        $valid = [];
        $tasks = Task::whereIn('id', $taskIds)->with('project:id,owner_id,member_ids,departments,settings', 'detail:id,task_id,responsible_user_id')->get()
            ->filter(function (Task $task) use ($user, $userId, &$valid) {
                if (!TaskAccessPolicy::canReassign($task, $user, $userId) || $task->ticket_id) return false;

                return !$userId || ($valid[$task->project_id ?? 0] ??= TaskAccessPolicy::assignableIds([$userId], $task->project) !== []);
            });

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
