<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ReorderTaskAction
{
    public function execute(Task $task, ?int $beforeTaskId, ?string $targetStatus = null): void
    {
        abort_unless($task->can_change_status, 403);

        $status = ($targetStatus && TaskStatus::tryFrom($targetStatus)) ? $targetStatus : $task->status;

        if ($status === TaskStatus::Done->value && empty($task->detail?->state)) {
            return;
        }

        DB::transaction(function () use ($task, $beforeTaskId, $status) {
            $ownerId = $task->assigned_to ?? $task->user_id;

            $siblings = Task::query()
                ->inRankColumn($task->project_id, $ownerId, $status)
                ->lockForUpdate()
                ->orderByRaw('rank IS NULL, rank')
                ->get(['id', 'rank'])
                ->reject(fn(Task $sibling) => $sibling->id === $task->id)
                ->values();

            $targetIndex = $beforeTaskId ? $siblings->search(fn(Task $sibling) => $sibling->id === $beforeTaskId) : false;

            if ($beforeTaskId && $targetIndex === false) {
                return;
            }

            $before = $targetIndex !== false
                ? ($targetIndex > 0 ? $siblings[$targetIndex - 1]->rank : null)
                : $siblings->last()?->rank;
            $after = $targetIndex !== false ? $siblings[$targetIndex]->rank : null;

            try {
                $rank = RankGenerator::between($before, $after);
            } catch (InvalidArgumentException | RuntimeException) {
                $insertAt = $targetIndex === false ? $siblings->count() : $targetIndex;
                $result = RankGenerator::rebalanceInsert($siblings->pluck('id')->values()->all(), $insertAt);

                foreach ($result['assignments'] as $id => $siblingRank) {
                    Task::whereKey($id)->update(['rank' => $siblingRank]);
                }

                $rank = $result['insertRank'];
            }

            $task->update(['status' => $status, 'rank' => $rank]);
        });
    }
}
