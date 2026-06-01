<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;

class CreateTaskAction
{
    public function execute(TaskForm $form): Task
    {
        $form->validate();

        return Task::create([
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'status'      => 'todo',
            'deadline'    => $form->resolveDeadline(),
            'user_id'     => auth()->id(),
            'assigned_to' => $form->selectedAssignee ?: null,
        ]);
    }
}
