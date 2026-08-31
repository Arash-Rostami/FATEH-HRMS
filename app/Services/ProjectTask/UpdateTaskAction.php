<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Traits\CleansAttachedFiles;
use App\Traits\ResolvesTaskDeadline;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTaskAction
{
    use CleansAttachedFiles, ResolvesTaskDeadline, StoresAttachedFiles;

    public function __construct(private CreateProjectAction $projects)
    {
    }

    public function execute(Task $task, TaskForm $form): void
    {
        abort_if(!$task->can_change_status || $task->ticket_id, 403);

        $form->validate();
        $form->validateAttachments();
        $project = $this->guardProjectVisibility($task, $form);
        $deadline = $this->resolveDeadline($form);
        $this->guardProjectDeadline($deadline, $project);

        DB::transaction(function () use ($task, $form, $deadline) {
            $this->guardTaskNotStale($task, $form);

            $newProjectId = $form->projectId ?: $this->projects->resolvePendingProject($form);
            $newAssignedTo = $form->selectedAssignee ?: null;
            $newOwnerId = $newAssignedTo ?? $task->user_id;
            $oldOwnerId = $task->assigned_to ?? $task->user_id;

            $oldBucket = $task->project_id ? ['project' => $task->project_id] : ['owner' => $oldOwnerId];
            $newBucket = $newProjectId ? ['project' => $newProjectId] : ['owner' => $newOwnerId];
            $bucketChanged = $oldBucket !== $newBucket;
            $priorityChanged = $form->priority !== $task->priority?->value;

            $rank = match (true) {
                $bucketChanged => Task::rankForPriority($newProjectId, $newOwnerId, $task->status, $form->priority),
                $priorityChanged => Task::rankForPriority($newProjectId, $newOwnerId, $task->status, $form->priority, $task->id),
                default => $task->rank,
            };

            $task->update([
                'title'       => $form->newTitle,
                'description' => $form->newDescription,
                'deadline'    => $deadline,
                'assigned_to' => $newAssignedTo,
                'project_id'  => $newProjectId,
                'labels'      => $form->labels,
                'priority'    => $form->priority,
                'rank'        => $rank,
            ]);

            $this->guardDetailNotStale($task, $form);

            $task->detail()->updateOrCreate([], [
                ...$form->detailAttributes(),
                'attachments' => [...$form->existingAttachments, ...$this->storeAttachments($form->attachments, 'task/attachments')],
            ]);
        });
    }

    private function guardTaskNotStale(Task $task, TaskForm $form): void
    {
        if ($form->taskUpdatedAt === null) return;

        $current = Task::query()->lockForUpdate()->find($task->id);

        if ($current && $current->updated_at->timestamp !== $form->taskUpdatedAt) {
            throw ValidationException::withMessages([
                'form' => 'این وظیفه توسط شخص دیگری تغییر کرده؛ لطفاً صفحه را بازخوانی کرده و دوباره تلاش کنید.',
            ]);
        }
    }

    private function guardDetailNotStale(Task $task, TaskForm $form): void
    {
        if ($form->detailUpdatedAt === null) return;

        $currentUpdatedAt = $task->detail()->lockForUpdate()->first()?->updated_at;

        if ($currentUpdatedAt !== null && $currentUpdatedAt->timestamp !== $form->detailUpdatedAt) {
            throw ValidationException::withMessages([
                'form' => 'همکاران یا پیوست‌های این وظیفه توسط شخص دیگری تغییر کرده‌اند؛ لطفاً صفحه را بازخوانی کرده و دوباره تلاش کنید.',
            ]);
        }
    }

    private function guardProjectVisibility(Task $task, TaskForm $form): ?Project
    {
        if (!$form->projectId) {
            return null;
        }

        $project = Project::visibleTo(auth()->user())
            ->whereKey($form->projectId)
            ->first(['id', 'settings']);

        if (!$project) {
            abort(403);
        }

        return $project;
    }

}
