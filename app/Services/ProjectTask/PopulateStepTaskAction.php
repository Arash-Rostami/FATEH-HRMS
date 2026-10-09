<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use App\Support\TaskAccessPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PopulateStepTaskAction
{
    public function execute(Workflow $wf, array $step, ?Project $project): ?Task
    {
        $assigneeIds = array_values(array_unique(array_map('intval', array_filter($step['assignee_user_ids'] ?? []))));

        if ($assigneeIds === []) {
            return null;
        }

        try {
            TaskAccessPolicy::assertAssignable($assigneeIds, $project);
        } catch (ValidationException) {
            return null;
        }

        $assigneeId = $assigneeIds[0];
        $creatorId = $wf->owner_id ?? $project?->owner_id ?? $assigneeId;

        return DB::transaction(function () use ($wf, $step, $project, $assigneeIds, $assigneeId, $creatorId) {
            $task = Task::create([
                'title' => $step['task_title'],
                'description' => $step['task_description'] ?? null,
                'status' => TaskStatus::Todo->value,
                'deadline' => $this->resolveDeadline($step, $project),
                'user_id' => $creatorId,
                'assigned_to' => $assigneeId,
                'project_id' => $wf->project_id,
                'rank' => Task::rankForPriority($wf->project_id, $assigneeId, 'todo', null),
            ]);

            $task->detail()->create([
                'collaborators' => array_slice($assigneeIds, 1) ?: null,
                'state' => TaskState::Completion->value,
                'action_source_domain' => 'چرخه کاری',
                'action_source' => "{$wf->name} · {$step['label']}",
            ]);

            return $task;
        });
    }

    private function resolveDeadline(array $step, ?Project $project): ?Carbon
    {
        if (!isset($step['deadline_days'])) {
            return $project?->charter()->defaultDeadline();
        }

        $deadline = now()->addDays($step['deadline_days'])->setTime(12, 0);
        $deadline = $project?->charter()->clamp($deadline) ?? $deadline;

        return $deadline->isBefore(today()) ? null : $deadline;
    }
}
