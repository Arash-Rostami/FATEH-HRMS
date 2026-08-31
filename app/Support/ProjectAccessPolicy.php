<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

final class ProjectAccessPolicy
{
    public static function canView(Project $project, ?User $user): bool
    {
        return $user !== null && $project->isVisibleTo($user);
    }

    public static function canManageAudience(Project $project, ?User $user): bool
    {
        return $user !== null && $project->owner_id === $user->id;
    }
}
