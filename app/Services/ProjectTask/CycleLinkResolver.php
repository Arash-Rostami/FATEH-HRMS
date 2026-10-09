<?php

namespace App\Services\ProjectTask;

use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\Workflow;
use Illuminate\Support\Collection;

class CycleLinkResolver
{
    private static bool $suspended = false;

    public function resolve(Collection $tasks): array
    {
        $projectIds = $tasks->pluck('project_id')->filter()->unique()->values();

        if ($projectIds->isEmpty()) {
            return [];
        }

        $wanted = $tasks->pluck('id')->flip();
        $links = [];

        Workflow::whereIn('project_id', $projectIds)
            ->where('status', Workflow::STATUS_ACTIVE)
            ->get(['id', 'name', 'steps', 'current_step'])
            ->each(function (Workflow $workflow) use ($wanted, &$links) {
                foreach ($workflow->steps ?? [] as $index => $step) {
                    $taskId = $step['task_id'] ?? null;

                    if (!$taskId || !$wanted->has($taskId) || ($links[$taskId]['is_current'] ?? false)) {
                        continue;
                    }

                    $links[$taskId] = [
                        'workflow_id' => $workflow->id,
                        'name' => $workflow->name,
                        'step_label' => $step['label'] ?? null,
                        'is_current' => $index === $workflow->current_step,
                    ];
                }
            });

        return $links;
    }

    public function prime(Collection $tasks): array
    {
        $links = $this->resolve($tasks);

        $tasks->each(fn(Task $task) => $task->setCycleLink($links[$task->id] ?? null));

        return $links;
    }

    public static function withoutSync(callable $callback): void
    {
        $previous = self::$suspended;
        self::$suspended = true;

        try {
            $callback();
        } finally {
            self::$suspended = $previous;
        }
    }

    public function syncAssignees(Task $task, ?array $collaborators = null): void
    {
        if (self::$suspended || !$task->project_id) {
            return;
        }

        $collaborators ??= TaskDetail::where('task_id', $task->id)->first(['id', 'task_id', 'collaborators'])?->collaborators ?? [];
        $ids = array_values(array_unique(array_map('intval', array_filter([$task->assigned_to, ...$collaborators]))));

        if ($ids === []) {
            return;
        }

        Workflow::forProject($task->project_id)
            ->where('status', Workflow::STATUS_ACTIVE)
            ->lockForUpdate()
            ->get()
            ->each(function (Workflow $workflow) use ($task, $ids) {
                $step = $workflow->currentStep();

                if (($step['task_id'] ?? null) !== $task->id) {
                    return;
                }

                $current = array_map('intval', $step['assignee_user_ids'] ?? []);

                if (array_diff($ids, $current) === [] && array_diff($current, $ids) === []) {
                    return;
                }

                $steps = $workflow->steps;
                $steps[$workflow->current_step]['assignee_user_ids'] = $ids;
                $workflow->update(['steps' => $steps]);
            });
    }
}
