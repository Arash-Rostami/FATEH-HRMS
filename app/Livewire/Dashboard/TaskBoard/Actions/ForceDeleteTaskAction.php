<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Models\Task;

class ForceDeleteTaskAction
{
    public function execute(Task $task): bool|null
    {
        return $task->forceDelete();
    }
}