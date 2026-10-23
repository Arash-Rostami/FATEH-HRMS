<?php

namespace App\Services\ProjectTask;

use App\Filament\Resources\TaskResource\Enums\TaskState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class TaskMetrics
{
    public const DUE_SOON_DAYS = 3;

    public const SETTLED_STATUSES = ['done', 'pending'];

    public const LABEL_BREACH = 'نقض SLA یا مهلت';

    public const LABEL_DUE_SOON = 'سررسید نزدیک';

    public static function active(EloquentBuilder|Builder $query, string $table = ''): EloquentBuilder|Builder
    {
        return $query
            ->whereNotIn($table . 'status', self::SETTLED_STATUSES)
            ->whereNull($table . 'archived_at')
            ->whereNull($table . 'deleted_at')
            ->whereNotExists(fn($q) => $q->selectRaw('1')->from('task_details')
                ->whereColumn('task_details.task_id', 'tasks.id')
                ->where('task_details.state', TaskState::Suspension->value));
    }

    public static function overdue(EloquentBuilder|Builder $query, string $table = ''): EloquentBuilder|Builder
    {
        return self::active($query, $table)->where($table . 'deadline', '<', now()->startOfDay());
    }

    public static function dueSoon(EloquentBuilder|Builder $query, string $table = ''): EloquentBuilder|Builder
    {
        return self::active($query, $table)->whereBetween($table . 'deadline', [
            now()->startOfDay(),
            now()->addDays(self::DUE_SOON_DAYS)->endOfDay(),
        ]);
    }

    public static function breached(EloquentBuilder|Builder $query, mixed $sla, mixed $deadlineCap, string $table = ''): EloquentBuilder|Builder
    {
        return self::active($query, $table)->where(function ($q) use ($sla, $deadlineCap, $table) {
            if ($sla !== null) {
                $q->orWhereRaw('(' . $table . 'deadline IS NULL AND TIMESTAMPDIFF(HOUR, ' . $table . 'created_at, NOW()) > ?)', [(int) $sla]);
            }
            if ($deadlineCap !== null) {
                $q->orWhere($table . 'deadline', '>', Carbon::parse($deadlineCap)->endOfDay());
            }
        });
    }

    public static function statusCounts(EloquentBuilder|Builder $query): array
    {
        return $query->reorder()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn($count) => (int) $count)
            ->all();
    }

    public static function totals(array $byStatus): array
    {
        $total = array_sum($byStatus);
        $done = (int) ($byStatus['done'] ?? 0);

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? round($done / $total * 100, 1) : 0.0,
        ];
    }
}
