<?php

namespace App\Livewire\Dashboard\Project\Presentation;

use App\Models\Task;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkflowPresenter
{
    public function isStuck(Workflow $workflow): bool
    {
        $step = $workflow->currentStep();
        if (!$step) {
            return false;
        }

        $activatedAt = $step['activated_at'] ?? $workflow->started_at;
        if (!$activatedAt) {
            return false;
        }

        $threshold = ($step['populate_task'] ?? false) && ($step['deadline_days'] ?? null) !== null
            ? max(1, (int) $step['deadline_days'])
            : 3;

        return Carbon::parse($activatedAt)->diffInDays(now()) >= $threshold;
    }

    public function stepNumber(Workflow $workflow): int
    {
        return $workflow->current_step !== null ? $workflow->current_step + 1 : 0;
    }

    public function canAct(?array $currentStep, bool $isOwner, int $userId): bool
    {
        return (bool) ($currentStep && (in_array($userId, $currentStep['assignee_user_ids'] ?? [], true) || $isOwner));
    }

    public function isPopulated(?array $currentStep): bool
    {
        return (bool) ($currentStep && ($currentStep['populate_task'] ?? false));
    }

    public function populatedTask(?array $currentStep, Collection $batchedTasks): ?Task
    {
        if (!$this->isPopulated($currentStep) || empty($currentStep['task_id'])) {
            return null;
        }

        return $batchedTasks[$currentStep['task_id']] ?? null;
    }
}
