<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class TaskAccessPolicy
{
    public static function canView(Task $task, ?User $user): bool
    {
        if (!$user) return false;

        if ($task->user_id === $user->id || $task->assigned_to === $user->id) return true;
        if (self::isCollaborator($task, $user) || $task->detail?->responsible_user_id === $user->id) return true;
        if (!$task->project_id) return false;

        if ($task->relationLoaded('project')) return (bool) $task->project?->isVisibleTo($user);

        return Project::whereKey($task->project_id)->visibleTo($user)->exists();
    }

    public static function canReply(Task $task, ?User $user): bool
    {
        return self::canView($task, $user);
    }

    public static function canChangeStatus(Task $task, ?User $user): bool
    {
        return $user !== null && !$task->ticket_id && (self::isWorker($task, $user) || self::isCollaborator($task, $user));
    }

    public static function canEdit(Task $task, ?User $user): bool
    {
        return self::canChangeStatus($task, $user);
    }

    public static function canReassign(Task $task, ?User $user, ?int $toUserId = null): bool
    {
        if (!$user) return false;

        return self::isWorker($task, $user)
            || ($toUserId === $user->id && $task->detail?->responsible_user_id === $user->id);
    }

    public static function canMoveProject(Task $task, ?User $user): bool
    {
        return $user !== null && ($task->user_id === $user->id || self::isProjectOwner($task, $user)) && !$task->isCycleLocked();
    }

    public static function canDelete(Task $task, ?User $user): bool
    {
        return $user !== null && !$task->ticket_id && ($task->user_id === $user->id || self::isProjectOwner($task, $user)) && !$task->isCycleLocked();
    }

    public static function canArchive(Task $task, ?User $user): bool
    {
        return self::canUnarchive($task, $user) && $task->status === 'done' && !$task->isPendingApproval();
    }

    public static function canUnarchive(Task $task, ?User $user): bool
    {
        return $user !== null
            && ($task->user_id === $user->id || $task->assigned_to === $user->id || self::isProjectOwner($task, $user));
    }

    public static function canUndoAssignment(Task $task, ?User $user): bool
    {
        if (!$user) return false;

        return $task->user_id === $user->id && $task->assigned_to && $task->assigned_to !== $user->id;
    }

    public static function canApprove(Task $task, ?User $user): bool
    {
        $project = $task->project;

        if (!$user || !$project) return false;

        if (ProjectAccessPolicy::canManageTasks($project, $user)) return true;

        return $task->detail?->responsible_user_id === $user->id
            && (!$project->owner_id || $project->owner?->status !== 'active');
    }

    public static function assignableIds(array $ids, ?Project $project = null): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        if ($ids === []) return [];

        $users = $project ? $project->charter()->rosterQuery() : User::visibleOnBoard();

        return array_values(array_intersect($ids, $users->whereIn('id', $ids)->pluck('id')->all()));
    }

    public static function assertAssignable(array $ids, ?Project $project = null, string $key = 'form'): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        if (count(self::assignableIds($ids, $project)) !== count($ids)) {
            throw ValidationException::withMessages([$key => 'یکی از افراد انتخاب‌شده فعال نیست یا به این وظیفه دسترسی ندارد.']);
        }
    }

    private static function isCollaborator(Task $task, User $user): bool
    {
        return in_array($user->id, $task->detail?->collaborators ?? [], true);
    }

    private static function isWorker(Task $task, User $user): bool
    {
        return $task->assigned_to === $user->id
            || ($task->user_id === $user->id && !$task->assigned_to)
            || self::isProjectOwner($task, $user);
    }

    private static function isProjectOwner(Task $task, User $user): bool
    {
        return $task->project_id && $task->project && ProjectAccessPolicy::canManageTasks($task->project, $user);
    }
}
