<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Models\Project;
use App\Models\Workflow;
use App\Support\ProjectAccessPolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaveWorkflowAction
{
    public const MAX_ASSIGNEES = 20;

    public function execute(Project $project, array $steps, string $name, ?Workflow $workflow = null): Workflow
    {
        abort_unless(ProjectAccessPolicy::canManageAudience($project, auth()->user()), 403);
        abort_if($project->trashed(), 403);
        abort_if($workflow && (!$workflow->isDraft() || $workflow->project_id !== $project->id), 403);

        Validator::make(['steps' => $steps, 'name' => $name], [
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*.label' => ['required', 'string', 'max:160'],
            'steps.*.assignee_user_ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_ASSIGNEES],
            'steps.*.assignee_user_ids.*' => ['integer'],
            'steps.*.type' => ['nullable', 'in:action,approval'],
            'steps.*.reject_to_step' => ['nullable', 'integer'],
            'steps.*.populate_task' => ['nullable', 'boolean'],
            'steps.*.task_title' => ['required_if:steps.*.populate_task,true', 'nullable', 'string', 'max:191'],
            'steps.*.task_description' => ['nullable', 'string', 'max:5000'],
            'steps.*.deadline_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [], [
            'name' => 'نام چرخه',
            'steps' => 'مراحل چرخه',
            'steps.*.label' => 'عنوان مرحله',
            'steps.*.assignee_user_ids' => 'مسئولان مرحله',
            'steps.*.assignee_user_ids.*' => 'مسئول مرحله',
            'steps.*.type' => 'نوع مرحله',
            'steps.*.reject_to_step' => 'مرحلهٔ بازگشت',
            'steps.*.populate_task' => 'ساخت وظیفه برای مرحله',
            'steps.*.task_title' => 'عنوان وظیفه',
            'steps.*.task_description' => 'شرح وظیفه',
            'steps.*.deadline_days' => 'مهلت مرحله به روز',
        ])->validate();

        $steps = Workflow::sanitizeSteps($steps);

        foreach ($steps as $index => $step) {
            if (($step['assignee_user_ids'] ?? []) === []) {
                throw ValidationException::withMessages(["steps.{$index}.assignee_user_ids" => 'برای هر مرحله حداقل یک مسئول از اعضای فعال پروژه انتخاب کنید.']);
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
                throw ValidationException::withMessages(["steps.{$index}.reject_to_step" => 'مرحلهٔ تأیید باید پس از یک مرحلهٔ دیگر باشد؛ برای بازگشت، یکی از مراحل قبلی را انتخاب کنید.']);
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
