<?php

namespace App\Services\ProjectTask;

use App\Models\Task;
use App\Models\User;
use App\Support\TaskAccessPolicy;

final class ApproveTaskAction
{
    public function execute(Task $task, ?User $actor): bool
    {
        if ($actor === null) {
            return false;
        }

        $task->loadMissing('project:id,settings,owner_id');

        if (!$task->isPendingApproval() || !TaskAccessPolicy::canApprove($task, $actor)) {
            return false;
        }

        return $task->forceFill([
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();
    }
}