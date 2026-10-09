<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Services\ProjectTask\CycleLinkResolver;
use App\Services\ProjectTask\PopulateStepTaskAction;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReassignWorkflowStepAction
{
    public function execute(Workflow $workflow, int $userId, array $newIds): void
    {
        DB::transaction(function () use ($workflow, $userId, $newIds) {
            $wf = Workflow::query()->lockForUpdate()->find($workflow->id);
            if (!$wf || !$wf->isActive()) return;

            $project = $wf->projectWithTrashed();
            if (!$project || $project->trashed()) return;

            $actor = User::find($userId);
            $isOwner = ProjectAccessPolicy::canManageAudience($project, $actor);

            abort_unless($isOwner || ($actor && $project->isVisibleTo($actor)), 403);
            abort_unless($wf->isAssignee($userId) || $isOwner, 403);

            $filtered = Workflow::filterAssignees($newIds, $project, PHP_INT_MAX);

            if (count($filtered) > 20) {
                throw ValidationException::withMessages(['assignee_user_ids' => 'حداکثر ۲۰ نفر قابل انتخاب است.']);
            }

            if ($filtered === []) {
                throw ValidationException::withMessages(['assignee_user_ids' => 'حداقل یک مسئول معتبر باید انتخاب شود.']);
            }

            $step = $wf->currentStep();
            if (!$step) return;

            $currentIds = $step['assignee_user_ids'] ?? [];
            $unchanged = collect($currentIds)->unique()->sort()->values()->all() === collect($filtered)->unique()->sort()->values()->all();
            $populates = $step['populate_task'] ?? false;
            $task = $populates && !empty($step['task_id']) ? Task::with('detail')->where('project_id', $wf->project_id)->find($step['task_id']) : null;
            $needsTask = $populates && !$task;

            if ($unchanged && !$needsTask) {
                return;
            }

            $index = $wf->current_step;
            $steps = $wf->steps;
            $steps[$index]['assignee_user_ids'] = $filtered;

            if ($needsTask) {
                $steps[$index]['task_id'] = app(PopulateStepTaskAction::class)->execute($wf, $steps[$index], $project)?->id;
            } elseif ($populates && !$unchanged) {
                CycleLinkResolver::withoutSync(fn() => $this->syncTask($task, $currentIds, $filtered));
            }

            $wf->steps = $steps;
            $wf->recordEvent('reassigned', $index, $userId, true, [
                'added' => array_values(array_diff($filtered, $currentIds)),
                'removed' => array_values(array_diff($currentIds, $filtered)),
            ]);
        });
    }

    private function syncTask(Task $task, array $oldIds, array $newIds): void
    {
        if ($task->isGenuinelyDone()) return;

        $moved = $task->assigned_to !== $newIds[0];
        $attributes = ['assigned_to' => $newIds[0]];

        if ($moved) {
            $attributes['rank'] = Task::rankForPriority($task->project_id, $newIds[0], $task->status, $task->priority?->value, $task->id);
        }

        $task->update($attributes);

        $collaborators = array_values(array_unique([...array_diff($task->detail?->collaborators ?? [], $oldIds, [$newIds[0]]), ...array_slice($newIds, 1)]));
        $task->detail?->update(['collaborators' => $collaborators ?: null]);
    }
}
