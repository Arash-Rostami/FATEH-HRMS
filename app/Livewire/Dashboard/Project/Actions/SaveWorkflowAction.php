<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Project;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveWorkflowAction
{
    public function execute(Project $project, array $steps, string $name, ?Workflow $workflow = null): Workflow
    {
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);
        abort_if($project->trashed(), 403);
        abort_if($workflow && (!$workflow->isDraft() || $workflow->project_id !== $project->id), 403);

        Validator::make(['steps' => $steps, 'name' => $name], [
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*.label' => ['required', 'string', 'max:160'],
            'steps.*.assignee_user_ids' => ['required', 'array', 'min:1', 'max:20'],
            'steps.*.assignee_user_ids.*' => ['integer'],
            'steps.*.type' => ['nullable', 'in:action,approval'],
            'steps.*.reject_to_step' => ['nullable', 'integer'],
            'steps.*.populate_task' => ['nullable', 'boolean'],
            'steps.*.task_title' => ['required_if:steps.*.populate_task,true', 'nullable', 'string', 'max:191'],
            'steps.*.task_description' => ['nullable', 'string', 'max:5000'],
            'steps.*.deadline_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ])->validate();

        $steps = Workflow::sanitizeSteps($steps);

        foreach ($steps as $index => $step) {
            if (($step['assignee_user_ids'] ?? []) === []) {
                throw ValidationException::withMessages(["steps.{$index}.assignee_user_ids" => 'حداقل یک نفر باید انتخاب شود.']);
            }

            $type = $step['type'] ?? 'action';
            $steps[$index]['type'] = $type;

            if (($step['populate_task'] ?? false) && $type === 'approval') {
                throw ValidationException::withMessages(["steps.{$index}.populate_task" => 'مرحله تأیید نمی‌تواند وظیفه بسازد.']);
            }

            if ($type !== 'approval') {
                continue;
            }

            $rejectTo = $step['reject_to_step'] ?? null;

            if ($index === 0 || ($rejectTo !== null && ($rejectTo < 0 || $rejectTo >= $index))) {
                throw ValidationException::withMessages(["steps.{$index}.reject_to_step" => 'مرحله بازگشت نامعتبر است.']);
            }

            $steps[$index]['reject_to_step'] = $rejectTo ?? ($index - 1);
        }

        if ($workflow) {
            $workflow->update(['name' => trim($name), 'steps' => $steps]);

            return $workflow;
        }

        return Workflow::create([
            'name' => trim($name),
            'owner_id' => auth()->id(),
            'project_id' => $project->id,
            'steps' => $steps,
            'status' => Workflow::STATUS_DRAFT,
        ]);
    }
}
