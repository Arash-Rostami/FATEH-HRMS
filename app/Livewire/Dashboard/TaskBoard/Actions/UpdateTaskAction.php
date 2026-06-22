<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;

class UpdateTaskAction
{
    public function execute(Task $task, TaskForm $form): void
    {
        abort_if(!$task->can_change_status, 403);

        $form->validate();
        $this->validateAttachments($form);

        $task->update([
            'title'       => $form->newTitle,
            'description' => $form->newDescription,
            'deadline'    => $this->resolveDeadline($form),
            'assigned_to' => $form->selectedAssignee ?: null,
        ]);

        $task->detail()->updateOrCreate([], [
            ...$form->detailAttributes(),
            'attachments' => [...$form->existingAttachments, ...$this->storeAttachments($form)],
        ]);
    }

    private function resolveDeadline(TaskForm $form): ?Carbon
    {
        if (!$form->deadlineYear || !$form->deadlineMonth || !$form->deadlineDay) return null;

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
            'attachments' => 'array',
            'attachments.*' => 'file|max:4096|mimes:pdf,jpg,jpeg,png,docx,xlsx',
        ])->validate();

        if (count($form->existingAttachments) + count($form->attachments) > 5) {
            throw ValidationException::withMessages([
                'attachments' => 'حداکثر ۵ فایل می‌توانید ضمیمه کنید.',
            ]);
        }
    }
}
