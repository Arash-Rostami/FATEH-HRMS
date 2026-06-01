<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;

class UpdateTaskAction
{
    public function execute(Task $task, TaskForm $form): void
    {
        $form->validate();

        $task->update([
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'deadline'    => $form->resolveDeadline(),
            'assigned_to' => $form->selectedAssignee ?: null,
        ]);
    }
}
