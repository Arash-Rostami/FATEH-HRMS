<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;

class HandleWorkflowTransition
{
    public function execute(Workflow $wf): void
    {
        $event = $this->resolveEvent($wf);
        $project = $wf->projectWithTrashed();
        $steps = $wf->steps;

        if ($wf->isActive() && $wf->wasChanged('current_step')) {
            $this->populateTaskIfNeeded($wf, $project, $steps);
        }

        $this->mirrorToActivity($wf, $event, $project, $steps);
    }

    private function populateTaskIfNeeded(Workflow $wf, ?Project $project, array $steps): void
    {
        $step = $wf->current_step === null ? null : ($steps[$wf->current_step] ?? null);
        if (!$step || !($step['populate_task'] ?? false)) return;

        $existingTaskId = $step['task_id'] ?? null;
        $task = null;

        if ($existingTaskId) {
            $candidate = Task::find($existingTaskId);
            $genuinelyDone = $candidate && $candidate->isGenuinelyDone();

            if ($candidate && !$genuinelyDone && $candidate->project_id === $wf->project_id) {
                $task = $candidate;
            }
        }

        if (!$task) {
            $task = app(PopulateStepTaskAction::class)->execute($wf, $step, $project);
        }

        if ($task->id !== $existingTaskId) {
            $steps[$wf->current_step]['task_id'] = $task->id;
            $wf->steps = $steps;
            $wf->saveQuietly();
        }
    }

    private function mirrorToActivity(Workflow $wf, string $event, ?Project $project, array $steps): void
    {
        if (!$project) return;

        $fromIndex = $wf->getOriginal('current_step') ?? $wf->current_step;
        $log = $wf->step_log ?? [];
        $lastLogEntry = $log[array_key_last($log)] ?? null;
        $actor = $this->resolveActor($wf, $event, $lastLogEntry);

        $payload = [
            'event' => $event,
            'step' => $fromIndex,
            'label' => $steps[$fromIndex]['label'] ?? null,
            'cycle' => $wf->name,
        ];

        if (in_array($event, ['advanced', 'rejected'], true)) {
            $payload['to_step'] = $wf->current_step;
        }

        if (in_array($event, ['advanced', 'rejected', 'completed'], true) && isset($lastLogEntry['note'])) {
            $payload['note'] = $lastLogEntry['note'];
        }

        app(ActivityLogger::class)->system($project, $actor, TaskActivityType::WorkflowStep, $payload);
    }

    private function resolveEvent(Workflow $wf): string
    {
        if ($wf->wasChanged('status') && $wf->isCancelled()) return 'cancelled';
        if ($wf->wasChanged('status') && $wf->isCompleted()) return 'completed';
        if ($wf->getOriginal('current_step') === null) return 'started';
        if ($wf->wasChanged('current_step') && $wf->current_step < $wf->getOriginal('current_step')) return 'rejected';

        return 'advanced';
    }

    private function resolveActor(Workflow $wf, string $event, ?array $lastLogEntry): ?User
    {
        if (in_array($event, ['advanced', 'rejected', 'completed'], true) && isset($lastLogEntry['completed_by'])) {
            return User::find($lastLogEntry['completed_by']);
        }

        return User::find(auth()->id() ?? $wf->owner_id);
    }
}
