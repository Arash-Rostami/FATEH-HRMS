<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class BulkDeleteTasksAction
{
    public function execute(array $taskIds): int
    {
        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_delete);

        if ($tasks->isEmpty()) {
            return 0;
        }

        Task::whereIn('id', $tasks->modelKeys())->delete();

        DB::afterCommit(fn() => StateService::flush());

        return $tasks->count();
    }
}
