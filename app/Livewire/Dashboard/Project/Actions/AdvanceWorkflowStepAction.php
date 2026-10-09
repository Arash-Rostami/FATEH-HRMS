<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\DB;

class AdvanceWorkflowStepAction
{
    public function execute(int $workflowId, int $userId, int $expectedStep, bool $approved = true, bool $force = false, ?string $note = null): bool
    {
        return $this->run($workflowId, $userId, $expectedStep, $approved, $force, true, $note);
    }

    private function run(int $workflowId, int $userId, int $expectedStep, bool $approved, bool $force, bool $enforceGate, ?string $note = null): bool
    {
        return DB::transaction(function () use ($workflowId, $userId, $expectedStep, $approved, $force, $enforceGate, $note) {
            $wf = Workflow::query()->lockForUpdate()->find($workflowId);
            if (!$wf || !$wf->isActive()) return false;
            if ($wf->current_step !== $expectedStep) return false;

            $project = $wf->projectWithTrashed();
            if (!$project || $project->trashed()) return false;

            $step = $wf->currentStep();
            if (!$step) return false;

            if ($enforceGate) {
                $actor = User::find($userId);
                $isOwner = ProjectAccessPolicy::canManageAudience($project, $actor);
                abort_unless($isOwner || ($actor && $project->isVisibleTo($actor)), 403);
                abort_unless($force ? $isOwner : ($wf->isAssignee($userId) || $isOwner), 403);
            }

            if ($step['populate_task'] ?? false) {
                $task = isset($step['task_id']) ? Task::find($step['task_id']) : null;
                $genuinelyDone = $task && $task->isGenuinelyDone();
                if (!$genuinelyDone && !$force) return false;
            }

            $log = $wf->step_log ?? [];
            $logEntry = ['step' => $wf->current_step, 'completed_by' => $userId, 'completed_at' => now()->toISOString()];
            if (($step['type'] ?? 'action') === 'approval') $logEntry['approved'] = $approved;
            if ($force) $logEntry['forced'] = true;
            if (isset($step['task_id'])) $logEntry['task_id'] = $step['task_id'];
            if ($note !== null && $note !== '') $logEntry['note'] = $note;
            $log[] = $logEntry;

            if (($step['type'] ?? 'action') === 'approval' && !$approved) {
                $targetStep = $step['reject_to_step'];
                $steps = $wf->steps;
                $steps[$targetStep]['activated_at'] = now()->toISOString();
                $wf->update(['step_log' => $log, 'current_step' => $targetStep, 'steps' => $steps]);
                return true;
            }

            $isLast = $wf->current_step >= count($wf->steps) - 1;
            $nextStep = $wf->current_step + 1;
            $steps = $wf->steps;
            if ($force) $this->stopOpenTask($steps, $wf->current_step);
            if (!$isLast) $steps[$nextStep]['activated_at'] = now()->toISOString();
            $wf->update([
                'step_log' => $log,
                'steps' => $steps,
                'current_step' => $isLast ? $wf->current_step : $nextStep,
                'status' => $isLast ? Workflow::STATUS_COMPLETED : Workflow::STATUS_ACTIVE,
                'completed_at' => $isLast ? now() : null,
            ]);
            return true;
        });
    }

    public function stopOpenTask(array &$steps, int $index): void
    {
        $taskId = $steps[$index]['task_id'] ?? null;
        $task = $taskId ? Task::with('detail')->find($taskId) : null;

        if (!$task) {
            $steps[$index]['task_id'] = null;
            return;
        }

        if ($task->status === 'done') return;

        $steps[$index]['task_id'] = null;
        $task->detail?->update(['state' => TaskState::Suspension->value]);
    }

    public function detachStep(Task $task, ?int $projectId = null): void
    {
        $projectId ??= $task->project_id;
        if (!$projectId) return;

        DB::transaction(function () use ($task, $projectId) {
            Workflow::forProject($projectId)
                ->whereIn('status', [Workflow::STATUS_ACTIVE, Workflow::STATUS_DRAFT])
                ->lockForUpdate()
                ->get()
                ->each(function (Workflow $wf) use ($task) {
                    $steps = $wf->steps;
                    $index = collect($steps)->search(fn (array $step) => (int) ($step['task_id'] ?? 0) === $task->id);
                    if ($index === false) return;

                    $steps[$index]['task_id'] = null;
                    $wf->steps = $steps;
                    $wf->recordEvent('task_detached', $index, auth()->id() ?? $wf->owner_id);
                });
        });
    }

    public function completeForTask(Task $task): bool
    {
        if (!$task->project_id) return false;

        $workflow = Workflow::query()
            ->where('project_id', $task->project_id)
            ->where('status', Workflow::STATUS_ACTIVE)
            ->select(['id', 'owner_id', 'current_step', 'steps'])
            ->cursor()
            ->first(fn (Workflow $wf) => ($wf->currentStep()['task_id'] ?? null) === $task->id);

        if (!$workflow) return false;

        return $this->run($workflow->id, auth()->id() ?? $task->assigned_to ?? $workflow->owner_id, $workflow->current_step, true, false, false);
    }
}
