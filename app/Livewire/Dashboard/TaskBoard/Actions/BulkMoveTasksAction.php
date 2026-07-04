<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class BulkMoveTasksAction
{
    public function execute(array $taskIds, string $status): int
    {
        if (!TaskStatus::tryFrom($status)) {
            return 0;
        }

        $tasks = Task::whereIn('id', $taskIds)->get()->filter(fn(Task $task) => $task->can_change_status);

        if ($tasks->isEmpty()) {
            return 0;
        }

        Task::whereIn('id', $tasks->modelKeys())->update(['status' => $status]);

        DB::afterCommit(fn() => StateService::flush());

        return $tasks->count();
    }
}
