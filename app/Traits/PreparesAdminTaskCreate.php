<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\Task;
use App\Support\TaskAccessPolicy;

use function Illuminate\Support\enum_value;

trait PreparesAdminTaskCreate
{
    protected function prepareTaskCreate(array $data, array $people = [], string $errorKey = 'data.assigned_to'): array
    {
        $projectId = $data['project_id'] ?? null;

        $project = $projectId ? Project::find($projectId) : null;

        TaskAccessPolicy::assertAssignable([$data['assigned_to'] ?? null, ...$people], $project, $errorKey);

        if ($project) {
            $project->charter()->assertLabels($data['labels'] ?? [], [], str_replace('assigned_to', 'labels', $errorKey));
            blank($data['deadline'] ?? null) && $data['deadline'] = $project->charter()->defaultDeadline();
        }

        $data['rank'] = Task::rankForPriority(
            $projectId,
            $data['assigned_to'] ?? $data['user_id'] ?? auth()->id(),
            enum_value($data['status'] ?? 'todo'),
            enum_value($data['priority'] ?? null)
        );

        return $data;
    }

    protected function ensureTaskDetail(Task $task): void
    {
        $detail = $task->detail()->firstOrCreate([]);

        if ($detail->department_id === null && $department = $task->project?->charter()->defaultDepartment()) {
            $detail->update(['department_id' => $department]);
        }
    }
}
