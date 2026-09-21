<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use Carbon\Carbon;

class PopulateStepTaskAction
{
    public function execute(Workflow $wf, array $step, ?Project $project): Task
    {
        $assigneeId = $step['assignee_user_ids'][0];
        $creatorId = $wf->owner_id ?? $project?->owner_id ?? $assigneeId;

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
            'collaborators' => array_slice($step['assignee_user_ids'], 1) ?: null,
            'state' => TaskState::Completion->value,
            'action_source_domain' => 'چرخه کاری',
            'action_source' => "{$wf->name} · {$step['label']}",
        ]);

        return $task;
    }

    private function resolveDeadline(array $step, ?Project $project): ?Carbon
    {
        if (!isset($step['deadline_days'])) {
            return null;
        }

        $deadline = now()->addDays($step['deadline_days'])->setTime(12, 0);

        if ($project?->deadlineCapExceeded($deadline)) {
            $deadline = Carbon::parse($project->setting('deadline'))->endOfDay();
        }

        return $deadline;
    }
}
