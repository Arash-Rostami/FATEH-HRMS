<?php

namespace App\Models\Concerns;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Services\ProjectTask\RankGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

trait HasBoardRanking
{
    public static function nextRank(?int $projectId, int $ownerId, string $status): string
    {
        $firstRank = static::query()
            ->inRankColumn($projectId, $ownerId, $status)
            ->whereNotNull('rank')
            ->orderBy('rank')
            ->lockForUpdate()
            ->value('rank');

        return RankGenerator::between(null, $firstRank);
    }

    public static function rankForPriority(?int $projectId, int $ownerId, string $status, ?string $priority, ?int $excludeId = null): string
    {
        $tier = TaskPriority::tryFrom($priority ?? '')?->tier() ?? 0;

        $siblings = static::query()
            ->inRankColumn($projectId, $ownerId, $status)
            ->whereNotNull('rank')
            ->when($excludeId, fn(Builder $q) => $q->whereKeyNot($excludeId))
            ->orderBy('rank')
            ->lockForUpdate()
            ->get(['id', 'rank', 'priority']);

        $before = null;
        $after = null;
        $insertIndex = $siblings->count();

        foreach ($siblings as $index => $sibling) {
            $siblingTier = $sibling->priority?->tier() ?? 0;

            if ($siblingTier < $tier) {
                $after = $sibling->rank;
                $insertIndex = $index;
                break;
            }

            $before = $sibling->rank;
        }

        try {
            return RankGenerator::between($before, $after);
        } catch (InvalidArgumentException|RuntimeException) {
            $result = RankGenerator::rebalanceInsert($siblings->pluck('id')->values()->all(), $insertIndex);

            static::bulkAssignRanks($result['assignments']);

            return $result['insertRank'];
        }
    }

    public static function bulkAssignRanks(array $assignments): void
    {
        if ($assignments === []) {
            return;
        }

        $ids = array_keys($assignments);
        $bindings = [];

        foreach ($assignments as $id => $rank) {
            $bindings[] = $id;
            $bindings[] = $rank;
        }

        $table = (new static)->getTable();
        $cases = str_repeat('WHEN ? THEN ? ', count($assignments));
        $in = implode(',', array_fill(0, count($ids), '?'));

        DB::statement(
            "UPDATE `{$table}` SET `rank` = CASE `id` {$cases}END WHERE `id` IN ({$in})",
            [...$bindings, ...$ids]
        );
    }

    public function scopeInRankColumn(Builder $query, ?int $projectId, int $ownerId, string $status): void
    {
        $query->where('status', $status);

        if ($projectId) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id')->forUser($ownerId);
        }
    }

    protected function rerankIfMoved(): void
    {
        $ownerMoved = !$this->project_id
            && ($this->assigned_to ?? $this->user_id) !== ($this->getOriginal('assigned_to') ?? $this->getOriginal('user_id'));

        if ($this->isDirty('rank') || !($ownerMoved || $this->isDirty(['status', 'project_id', 'priority']))) return;

        $this->rank = static::rankForPriority($this->project_id, $this->assigned_to ?? $this->user_id, $this->status, $this->priority?->value, $this->id);
    }
}