<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Support\ProjectCharter;
use App\Support\TaskAccessPolicy;
use App\Support\FileNamer;
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
        abort_if(!TaskAccessPolicy::canEdit($task, auth()->user()) || $task->ticket_id, 403);

        $form->validate();
        $form->validateAttachments();

        if ($task->status === 'done' && empty($form->state) && !empty($task->detail?->state)) {
            throw ValidationException::withMessages(['form.state' => 'برای تغییر تعیین تکلیف ابتدا وظیفه را بازگشایی کنید.']);
        }

        $project =$this->guardProjectVisibility($task, $form);
        $people = [$form->selectedAssignee, $form->responsibleUserId, ...$form->collaborators];
        $projectChanged = (int)$form->projectId !== (int)$task->project_id;
        TaskAccessPolicy::assertAssignable(
            $projectChanged
                ? $people
                : array_diff($people, [$task->assigned_to, $task->detail?->responsible_user_id, ...($task->detail?->collaborators ?? [])]),
            $project
        );
        $deadline = $this->resolveTaskDeadline($form, $project, $task->deadline, $projectChanged);
        $labels = ProjectCharter::normalizeLabels($form->labels);
        $project?->charter()->assertLabels($labels, $projectChanged ? [] : ($task->labels ?? []), 'form.labels');

        $dropped = [];

        DB::transaction(function () use ($task, $form, $deadline, $labels, &$dropped) {
            $this->guardTaskNotStale($task, $form);

            $newProjectId = $form->projectId ?: $this->projects->resolvePendingProject($form);
            abort_if((int)$newProjectId !== (int)$task->project_id && !TaskAccessPolicy::canMoveProject($task, auth()->user()), 403);
            $newAssignedTo = $form->selectedAssignee ? (int)$form->selectedAssignee : null;
            abort_if(
                $newAssignedTo !== $task->assigned_to
                && !TaskAccessPolicy::canReassign($task, auth()->user(), $newAssignedTo)
                && !TaskAccessPolicy::canUndoAssignment($task, auth()->user()),
                403
            );
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
                'labels'      => $labels,
                'priority'    => $form->priority,
                'rank'        => $rank,
            ]);

            $stored = $this->guardDetailNotStale($task, $form)?->attachments ?? [];
            $keptPaths = array_column($form->existingAttachments, 'path');
            [$kept, $dropped] = collect($stored)->partition(fn($attachment) => in_array($attachment['path'] ?? null, $keptPaths, true));
            $dropped = $dropped->values()->all();

            $task->detail()->updateOrCreate([], [
                ...$form->detailAttributes(),
                'attachments' => [...$kept->values()->all(), ...$this->storeAttachments($form->attachments, 'task/attachments', fn($file) => FileNamer::stored('task', $file))],
            ]);
        });

        static::deleteStoredFiles($dropped);
    }

    private function guardTaskNotStale(Task $task, TaskForm $form): void
    {
        if ($form->taskUpdatedAt === null) return;

        $current = Task::query()->lockForUpdate()->find($task->id);

        if ($current && ($current->updated_at->timestamp !== $form->taskUpdatedAt || ($form->taskVersion !== null && $current->version !== $form->taskVersion))) {
            throw ValidationException::withMessages([
                'form' => 'این وظیفه توسط شخص دیگری تغییر کرده؛ لطفاً صفحه را بازخوانی کرده و دوباره تلاش کنید.',
            ]);
        }
    }

    private function guardDetailNotStale(Task $task, TaskForm $form): ?TaskDetail
    {
        $detail = $task->detail()->lockForUpdate()->first();

        if ($form->detailUpdatedAt !== null && $detail?->updated_at !== null && $detail->updated_at->timestamp !== $form->detailUpdatedAt) {
            throw ValidationException::withMessages([
                'form' => 'همکاران یا پیوست‌های این وظیفه توسط شخص دیگری تغییر کرده‌اند؛ لطفاً صفحه را بازخوانی کرده و دوباره تلاش کنید.',
            ]);
        }

        return $detail;
    }

    private function guardProjectVisibility(Task $task, TaskForm $form): ?Project
    {
        if (!$form->projectId) {
            return null;
        }

        $project = Project::visibleTo(auth()->user())
            ->whereKey($form->projectId)
            ->first(['id', 'settings', 'owner_id', 'member_ids', 'departments']);

        if (!$project) {
            abort(403);
        }

        return $project;
    }

}
