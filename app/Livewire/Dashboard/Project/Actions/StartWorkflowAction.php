<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartWorkflowAction
{
    public function execute(Workflow $workflow): Workflow
    {
        $project = $workflow->projectWithTrashed();

        abort_if(!$project || $project->trashed(), 403);
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);
        abort_unless($workflow->isDraft(), 422);

        $steps = $workflow->steps;

        if ($steps === []) {
            throw ValidationException::withMessages(['steps' => 'چرخه باید حداقل یک مرحله داشته باشد.']);
        }

        $filteredPerStep = Workflow::filterAssigneesBatch(
            array_map(fn (array $step) => $step['assignee_user_ids'] ?? [], $steps),
            $project
        );

        foreach ($steps as $index => $step) {
            if ($filteredPerStep[$index] === []) {
                throw ValidationException::withMessages(["steps.{$index}.assignee_user_ids" => 'حداقل یک مسئول معتبر باید انتخاب شود.']);
            }

            $steps[$index]['assignee_user_ids'] = $filteredPerStep[$index];
        }

        $steps[0]['activated_at'] = now()->toISOString();

        return DB::transaction(function () use ($workflow, $steps) {
            $workflow->update([
                'steps' => $steps,
                'current_step' => 0,
                'status' => Workflow::STATUS_ACTIVE,
                'started_at' => now(),
            ]);

            return $workflow;
        });
    }
}
