<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Filament\Notifications\Notification;
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

            $isOwner = ProjectAccessPolicy::canManageAudience($project, User::find($userId));

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

            if (collect($currentIds)->unique()->sort()->values()->all() === collect($filtered)->unique()->sort()->values()->all()) {
                return;
            }

            $steps = $wf->steps;
            $steps[$wf->current_step]['assignee_user_ids'] = $filtered;
            $wf->update(['steps' => $steps]);

            if ($step['populate_task'] ?? false) {
                $this->syncTask($step['task_id'] ?? null, $filtered);
            }

            $actorName = User::find($userId)?->name;

            foreach (User::whereIn('id', array_diff($filtered, $currentIds))->get() as $user) {
                Notification::make()
                    ->title('واگذاری مرحله چرخه کاری')
                    ->body("مرحله «{$step['label']}» در چرخه «{$wf->name}» توسط {$actorName} به شما واگذار شد.")
                    ->success()
                    ->sendToDatabase($user);
            }
        });
    }

    private function syncTask(?int $taskId, array $newIds): void
    {
        if (!$taskId) return;

        $task = Task::with('detail')->find($taskId);
        if (!$task || $task->isGenuinelyDone()) return;

        $task->update(['assigned_to' => $newIds[0]]);
        $task->detail?->update(['collaborators' => array_slice($newIds, 1) ?: null]);
    }
}
