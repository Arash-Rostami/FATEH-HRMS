<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;

class UpdateTaskAction
{
    public function execute(Task $task, TaskForm $form): void
    {
        $form->validate();

        $data = [
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'deadline'    => $form->resolveDeadline(),
            'assigned_to' => $form->selectedAssignee ?: null,
        ];

        if ($data['assigned_to'] && $data['assigned_to'] != $task->user_id && $task->status === 'todo') {
            $data['status'] = 'delegated';
        } elseif (!$data['assigned_to'] && $task->status === 'delegated') {
            $data['status'] = 'todo';
        }

        $task->update($data);
    }
}
