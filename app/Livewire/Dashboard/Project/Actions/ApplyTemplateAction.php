<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Project;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;

class ApplyTemplateAction
{
    public function execute(Workflow $template, Project $project): Workflow
    {
        abort_unless($template->project_id === null, 404);
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);
        abort_if($project->trashed(), 403);

        return Workflow::create([
            'name' => $template->name,
            'owner_id' => auth()->id(),
            'project_id' => $project->id,
            'template_id' => $template->id,
            'steps' => Workflow::sanitizeSteps($template->steps),
            'status' => Workflow::STATUS_DRAFT,
        ]);
    }
}
