<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Morilog\Jalali\CalendarUtils;

class UpdateTaskAction
{
    public function execute(Task $task, TaskForm $form): void
    {
        abort_if(!$task->can_change_status || $task->ticket_id, 403);

        $form->validate();
        $this->validateAttachments($form);

        DB::transaction(function () use ($task, $form) {
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
        });
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
            ->map(function ($file) {
                $originalName = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $size = $file->getSize();

                $path = $file->store('task/attachments', 'public');

                return [
                    'path' => $path,
                    'name' => $originalName,
                    'mime' => $mime,
                    'size' => $size,
                ];
            })
            ->values()
            ->all();
    }

    private function validateAttachments(TaskForm $form): void
    {
        Validator::make(['attachments' => $form->attachments], [
            'attachments' => 'array',
            'attachments.*' => 'file|max:4096|mimes:jpg,jpeg,png,gif,bmp,webp,svg,pdf,doc,docx,xls,xlsx',
        ], [
            'attachments.*.max' => __('resources/task/strings.validation.attachments.max_size'),
            'attachments.*.mimes' => __('resources/task/strings.validation.attachments.mime_types'),
        ])->validate();

        if (count($form->existingAttachments) + count($form->attachments) > 5) {
            throw ValidationException::withMessages([
                'attachments' => __('resources/task/strings.validation.attachments.max_items'),
            ]);
        }
    }
}
