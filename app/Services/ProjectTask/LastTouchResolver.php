<?php

namespace App\Services\ProjectTask;

use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class LastTouchResolver
{
    public function resolve(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $touches = Reply::query()
            ->where('repliable_type', Task::class)
            ->whereIn('repliable_id', $taskIds)
            ->whereNotNull('user_id')
            ->whereIn('id', Reply::query()
                ->selectRaw('MAX(id)')
                ->where('repliable_type', Task::class)
                ->whereIn('repliable_id', $taskIds)
                ->whereNotNull('user_id')
                ->groupBy('repliable_id'))
            ->get(['repliable_id', 'user_id', 'created_at']);

        if ($touches->isEmpty()) {
            return [];
        }

        $names = User::query()
            ->whereIn('id', $touches->pluck('user_id')->unique())
            ->pluck('name', 'id');

        return $touches
            ->mapWithKeys(fn(Reply $reply) => [
                $reply->repliable_id => [
                    'user_id' => $reply->user_id,
                    'user_name' => $names[$reply->user_id] ?? '—',
                    'created_at' => $reply->created_at->toIso8601String(),
                ],
            ])
            ->all();
    }
}