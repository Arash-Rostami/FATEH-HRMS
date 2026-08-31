<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;
use Morilog\Jalali\Jalalian;

class DuplicateTaskAction
{
    public function __construct(private CreateTaskAction $createTaskAction)
    {
    }

    public function execute(Task $task, TaskForm $form): Task
    {
        $task->loadMissing('detail');
        $detail = $task->detail;

        $form->reset();
        $form->newTitle = $task->title . ' (کپی)';
        $form->newDescription = $task->description;
        $form->labels = $task->labels ?? [];
        $form->priority = $task->priority?->value;
        $form->projectId = $task->project_id;
        $form->selectedAssignee = auth()->id();
        $form->departmentId = $detail?->department_id;
        $form->unit = $detail?->unit;
        $form->section = $detail?->section;
        $form->project = $detail?->project;
        $form->scheme = $detail?->scheme;
        $form->collaborators = $detail?->collaborators ?? [];
        $form->responsibleUserId = $detail?->responsible_user_id;
        $form->meta = $detail?->meta ?? [];
        $form->checklist = collect($detail?->checklist ?? [])
            ->map(fn(array $item) => ['text' => $item['text'] ?? '', 'done' => false, 'weight' => (int) ($item['weight'] ?? 0)])
            ->all();

        if ($task->deadline) {
            $jalali = Jalalian::fromCarbon($task->deadline);
            $form->deadlineYear = (string) $jalali->getYear();
            $form->deadlineMonth = (string) $jalali->getMonth();
            $form->deadlineDay = (string) $jalali->getDay();
        }

        return $this->createTaskAction->execute($form);
    }
}
