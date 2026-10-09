<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Services\Menu\StateService;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class BulkDeleteTasksAction
{
    public function execute(array $taskIds): int
    {
        $user = auth()->user();
        $found = Task::whereIn('id', $taskIds)->whereNull('ticket_id')->with('project:id,owner_id', 'detail:id,task_id,collaborators,responsible_user_id')->get();
        app(CycleLinkResolver::class)->prime($found);
        $tasks = $found->filter(fn(Task $task) => TaskAccessPolicy::canDelete($task, $user));

        if ($tasks->isEmpty()) {
            return 0;
        }

        DB::transaction(fn() => $tasks->each->delete());

        DB::afterCommit(fn() => StateService::flush());

        return $tasks->count();
    }
}
