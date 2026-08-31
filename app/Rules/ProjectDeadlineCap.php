<?php

namespace App\Rules;

use App\Models\Project;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProjectDeadlineCap implements ValidationRule
{
    public function __construct(private readonly ?int $projectId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->projectId || !is_string($value) || blank($value) || !Carbon::hasFormat($value, 'Y-m-d')) {
            return;
        }

        $project = Project::find($this->projectId, ['id', 'settings']);

        if ($project?->deadlineCapExceeded(Carbon::parse($value))) {
            $fail(__('resources/task/strings.validation.project_deadline_cap'));
        }
    }
}