<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Task;
use App\Support\TaskAccessPolicy;
use App\Traits\CleansAttachedFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class DuplicateTaskAction
{
    use CleansAttachedFiles;

    private const SUFFIX = ' (کپی)';

    public function __construct(private CreateTaskAction $createTaskAction)
    {
    }

    public function execute(Task $task, TaskForm $form): Task
    {
        $task->loadMissing('detail', 'project');
        $detail = $task->detail;
        $collaborators = TaskAccessPolicy::assignableIds($detail?->collaborators ?? [], $task->project);
        $responsible = $detail?->responsible_user_id
            ? (TaskAccessPolicy::assignableIds([$detail->responsible_user_id], $task->project)[0] ?? null)
            : null;

        $form->reset();
        $form->newTitle = Str::limit($task->title, 255 - mb_strlen(self::SUFFIX), '') . self::SUFFIX;
        $form->newDescription = $task->description;
        $vocabulary = $task->project?->charter()->labels() ?? [];
        $form->labels = $vocabulary ? array_values(array_intersect($task->labels ?? [], $vocabulary)) : ($task->labels ?? []);
        $form->priority = $task->priority?->value;
        $form->projectId = $task->project_id;
        $form->selectedAssignee = auth()->id();
        $form->departmentId = $detail?->department_id;
        $form->unit = $detail?->unit;
        $form->section = $detail?->section;
        $form->project = $detail?->project;
        $form->scheme = $detail?->scheme;
        $form->collaborators = $collaborators;
        $form->responsibleUserId = $responsible;
        $form->meta = $detail?->meta ?? [];
        $form->checklist = collect($detail?->checklist ?? [])
            ->map(fn(array $item) => ['text' => $item['text'] ?? '', 'done' => false, 'weight' => (int) ($item['weight'] ?? 0)])
            ->all();

        if ($task->deadline && !$task->deadline->isBefore(today())) {
            $jalali = Jalalian::fromCarbon($task->deadline);
            $form->deadlineYear = (string) $jalali->getYear();
            $form->deadlineMonth = (string) $jalali->getMonth();
            $form->deadlineDay = (string) $jalali->getDay();
        }

        return DB::transaction(function () use ($form, $detail) {
            $copy = $this->createTaskAction->execute($form);
            $files = [];

            try {
                $files = $this->copyAttachments($detail?->attachments ?? []);
                $files === [] || $copy->detail()->update(['attachments' => $files]);
            } catch (\Throwable $e) {
                static::deleteStoredFiles($files);

                throw $e;
            }

            return $copy;
        });
    }

    private function copyAttachments(array $attachments): array
    {
        $disk = Storage::disk('public');
        $copied = [];

        foreach ($attachments as $item) {
            $path = $item['path'] ?? null;

            if (!is_string($path) || !static::isSafeStoredPath($path) || !$disk->exists($path)) continue;

            $target = 'task/attachments/' . Str::uuid() . '.' . pathinfo($path, PATHINFO_EXTENSION);
            $disk->copy($path, $target);
            $copied[] = [...$item, 'path' => $target];
        }

        return $copied;
    }
}
