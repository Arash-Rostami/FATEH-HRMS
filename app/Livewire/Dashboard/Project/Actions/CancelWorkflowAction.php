<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Project;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\DB;

class CancelWorkflowAction
{
    public function execute(Workflow $workflow): void
    {
        DB::transaction(function () use ($workflow) {
            $wf = Workflow::query()->lockForUpdate()->find($workflow->id);
            if (!$wf) return;

            $project = $wf->projectWithTrashed();

            abort_if(!$project, 403);
            abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);

            if (!$wf->isActive() && !$wf->isDraft()) return;

            $this->cancel($wf);
        });
    }

    public function cancel(Workflow $wf, ?string $reason = null): void
    {
        $steps = $wf->steps;

        if ($wf->isActive() && $wf->current_step !== null) {
            app(AdvanceWorkflowStepAction::class)->stopOpenTask($steps, $wf->current_step);
        }

        $log = $wf->step_log ?? [];

        if ($reason) {
            $log[] = ['step' => $wf->current_step ?? 0, 'completed_by' => auth()->id() ?? $wf->owner_id, 'completed_at' => now()->toISOString(), 'event' => $reason];
        }

        $wf->transitionReason = $reason;
        $wf->update(['status' => Workflow::STATUS_CANCELLED, 'steps' => $steps, 'step_log' => $log, 'completed_at' => now()]);
    }

    public function revive(Project $project): void
    {
        Workflow::forProject($project->id)
            ->where('status', Workflow::STATUS_CANCELLED)
            ->get()
            ->filter(fn (Workflow $wf) => (collect($wf->step_log)->last()['event'] ?? null) === 'project_deleted')
            ->each(function (Workflow $wf) {
                $wf->fill([
                    'status' => Workflow::STATUS_DRAFT,
                    'current_step' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'steps' => array_map(fn (array $step) => array_diff_key($step, ['task_id' => 1, 'activated_at' => 1]), $wf->steps),
                    'step_log' => array_values(array_filter($wf->step_log, fn (array $entry) => ($entry['event'] ?? null) !== 'project_deleted')),
                ]);
                $wf->recordEvent('restored', null, auth()->id(), false);
            });
    }
}
