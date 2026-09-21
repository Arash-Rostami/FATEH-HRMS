<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;

class SaveAsTemplateAction
{
    public function execute(Workflow $workflow): Workflow
    {
        $project = $workflow->projectWithTrashed();

        abort_if(!$project, 403);
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);

        return Workflow::create([
            'name' => $workflow->name,
            'owner_id' => auth()->id(),
            'project_id' => null,
            'steps' => Workflow::sanitizeSteps($workflow->steps),
            'status' => Workflow::STATUS_DRAFT,
        ]);
    }
}
