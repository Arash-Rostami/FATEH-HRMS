<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;

class CreateTaskAction
{
    public function execute(TaskForm $form): Task
    {
        $form->validate();

        $status = 'todo';
        $userId = auth()->id();
        $assignedTo = $form->selectedAssignee ?: null;

        if ($assignedTo && $assignedTo != $userId) {
            $status = 'delegated';
        }

        return Task::create([
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'status'      => $status,
            'deadline'    => $form->resolveDeadline(),
            'user_id'     => $userId,
            'assigned_to' => $assignedTo,
        ]);
    }
}
