<?php

namespace App\Livewire\Dashboard\Project\Actions;

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

            $wf->update(['status' => Workflow::STATUS_CANCELLED]);
        });
    }
}
