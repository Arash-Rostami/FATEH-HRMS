<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Services\Menu\StateService;
use App\Support\TaskAccessPolicy;
use Illuminate\Support\Facades\DB;

class BulkDeleteTasksAction
{
    public function execute(array $taskIds): int
    {
        $user = auth()->user();
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => TaskAccessPolicy::canDelete($task, $user) && !$task->ticket_id);

        if ($tasks->isEmpty()) {
            return 0;
        }

        Task::whereIn('id', $tasks->modelKeys())->delete();

        DB::afterCommit(fn() => StateService::flush());

        return $tasks->count();
    }
}
