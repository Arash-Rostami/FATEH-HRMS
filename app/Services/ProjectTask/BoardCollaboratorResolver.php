<?php

namespace App\Services\ProjectTask;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class BoardCollaboratorResolver
{
    public function resolve(EloquentCollection $tasks): array
    {
        $ids = $tasks->flatMap(fn(Task $task) => $task->detail?->collaborators ?? [])->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return User::whereIn('id', $ids)->with('profile')->get(['id', 'name'])
            ->mapWithKeys(fn(User $user) => [$user->id => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl(),
            ]])
            ->all();
    }
}
