<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

final class TaskAccessPolicy
{
    public static function canView(Task $task, ?User $user): bool
    {
        if (!$user) return false;

        if ($task->user_id === $user->id || $task->assigned_to === $user->id) return true;
        if (in_array($user->id, $task->detail?->collaborators ?? [], true)) return true;
        if (!$task->project_id) return false;

        return Project::whereKey($task->project_id)->visibleTo($user)->exists();
    }

    public static function canChangeStatus(Task $task, ?User $user): bool
    {
        if (!$user) return false;

        return $task->assigned_to === $user->id || ($task->user_id === $user->id && !$task->assigned_to);
    }

    public static function canDelete(Task $task, ?User $user): bool
    {
        return $user !== null && $task->user_id === $user->id;
    }

    public static function canUndoAssignment(Task $task, ?User $user): bool
    {
        if (!$user) return false;

        return $task->user_id === $user->id && $task->assigned_to && $task->assigned_to !== $user->id;
    }

    public static function canApprove(Task $task, ?User $user): bool
    {
        return $user !== null && $task->project?->owner_id === $user->id;
    }
}
