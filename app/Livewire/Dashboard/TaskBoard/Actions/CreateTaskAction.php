<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Morilog\Jalali\CalendarUtils;

class CreateTaskAction
{
    public function execute(TaskForm $form): Task
    {
        $form->validate();
        $this->validateAttachments($form);

        $task = Task::create([
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'status'      => 'todo',
            'deadline'    => $this->resolveDeadline($form),
            'user_id'     => auth()->id(),
            'assigned_to' => $form->selectedAssignee ?: null,
        ]);

        $task->detail()->create([
            ...$form->detailAttributes(),
            'attachments' => $this->storeAttachments($form),
        ]);

        return $task;
    }

    private function resolveDeadline(TaskForm $form): ?Carbon
    {
        if (!$form->deadlineYear || !$form->deadlineMonth || !$form->deadlineDay) return null;

        if (!CalendarUtils::checkDate((int) $form->deadlineYear, (int) $form->deadlineMonth, (int) $form->deadlineDay, true)) {
            throw new InvalidArgumentException('Invalid Jalali date.');
        }

        return CalendarUtils::createCarbonFromFormat(
            'Y/m/d',
            sprintf('%s/%02d/%02d', $form->deadlineYear, $form->deadlineMonth, $form->deadlineDay)
        );
    }

    private function storeAttachments(TaskForm $form): array
    {
        return collect($form->attachments)
            ->map(fn($file) => ['file' => $file->store('task/attachments', 'public')])
            ->values()
            ->all();
    }

    private function validateAttachments(TaskForm $form): void
    {
        Validator::make(['attachments' => $form->attachments], [
            'attachments' => 'array|max:5',
            'attachments.*' => 'file|max:4096|mimes:jpg,jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx',
        ], [
            'attachments.max' => __('resources/task/strings.validation.attachments.max_items'),
            'attachments.*.max' => __('resources/task/strings.validation.attachments.max_size'),
            'attachments.*.mimes' => __('resources/task/strings.validation.attachments.mime_types'),
        ])->validate();
    }
}
