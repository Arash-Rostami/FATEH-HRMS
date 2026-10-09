<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Support\ProjectCharter;
use App\Support\TaskAccessPolicy;
use App\Traits\CleansAttachedFiles;
use App\Traits\ResolvesTaskDeadline;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    use CleansAttachedFiles, ResolvesTaskDeadline, StoresAttachedFiles;

    public function __construct(private CreateProjectAction $projects)
    {
    }

    public function execute(TaskForm $form): Task
    {
        $form->validate();
        $form->validateAttachments();
        $project = $this->guardProjectVisibility($form);
        TaskAccessPolicy::assertAssignable([$form->selectedAssignee, $form->responsibleUserId, ...$form->collaborators], $project);
        $deadline = $this->resolveTaskDeadline($form, $project, creating: true);
        $labels = ProjectCharter::normalizeLabels($form->labels);
        $project?->charter()->assertLabels($labels, [], 'form.labels');
        blank($form->departmentId) && $form->departmentId = $project?->charter()->defaultDepartment();

        return DB::transaction(function () use ($form, $deadline, $labels) {
            $projectId = $form->projectId ?: $this->projects->resolvePendingProject($form);
            $ownerId = $form->selectedAssignee ?: auth()->id();

            $task = Task::create([
                'title'       => $form->newTitle,
                'description' => $form->newDescription,
                'status'      => TaskStatus::Todo->value,
                'deadline'    => $deadline,
                'user_id'     => auth()->id(),
                'assigned_to' => $form->selectedAssignee ?: null,
                'project_id'  => $projectId,
                'rank'        => Task::rankForPriority($projectId, $ownerId, 'todo', $form->priority),
                'labels'      => $labels,
                'priority'    => $form->priority,
            ]);

            $task->detail()->create([
                ...$form->detailAttributes(),
                'attachments' => $this->storeAttachments($form->attachments, 'task/attachments'),
            ]);

            return $task;
        });
    }

    private function guardProjectVisibility(TaskForm $form): ?Project
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
