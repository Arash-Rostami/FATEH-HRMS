<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportingService
{
    private const SORT_FIELDS = ['deadline', 'last_activity_at', 'created_at', 'priority'];
    private const FILTER_OPTIONS_FRESH_SECONDS = 60;
    private const FILTER_OPTIONS_STALE_SECONDS = 300;

    public function visibleProjectIds(User $user): Collection
    {
        return Project::visibleTo($user)->pluck('id');
    }

    public function query(User $user, array $filters = [], bool $withCounts = true): Builder
    {
        $visibleProjectIds = $this->visibleProjectIds($user);

        $query = Task::query()
            ->with(['project:id,name,settings', 'assignee:id,name', 'detail', 'creator:id,name'])
            ->where(function (Builder $q) use ($visibleProjectIds, $user) {
                $q->whereIn('project_id', $visibleProjectIds)
                    ->orWhere('user_id', $user->id)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereHas('detail', fn(Builder $d) => $d->whereJsonContains('collaborators', $user->id));
            })
            ->when($filters['project_id'] ?? null, fn(Builder $q, $v) => $q->where('project_id', $v))
            ->when($filters['department'] ?? null, fn(Builder $q, $v) => $q->whereHas('detail', fn(Builder $d) => $d->where('department_id', $v)))
            ->when($filters['assignee_id'] ?? null, fn(Builder $q, $v) => $q->where('assigned_to', $v))
            ->when($filters['status'] ?? null, fn(Builder $q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn(Builder $q, $v) => $q->where('priority', $v))
            ->when($filters['label'] ?? null, fn(Builder $q, $v) => $q->whereJsonContains('labels', $v))
            ->when($filters['scheme'] ?? null, fn(Builder $q, $v) => $q->whereHas('detail', fn(Builder $d) => $d->where('scheme', $v)))
            ->when($filters['action_source_domain'] ?? null, fn(Builder $q, $v) => $q->whereHas('detail', fn(Builder $d) => $d->where('action_source_domain', $v)))
            ->when($filters['action_source'] ?? null, fn(Builder $q, $v) => $q->whereHas('detail', fn(Builder $d) => $d->where('action_source', $v)))
            ->when($filters['search'] ?? null, function (Builder $q, $v) {
                $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $v) . '%';
                $q->where(fn(Builder $s) => $s->where('title', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhereHas('detail', fn(Builder $d) => $d->where('description', 'like', $needle)));
            })
            ->when($withCounts, fn(Builder $q) => $q
                ->addSelect(['last_activity_at' => Reply::query()
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('repliable_id', 'tasks.id')
                    ->where('repliable_type', Task::class),
                ])
                ->addSelect(['replies_count' => Reply::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('repliable_id', 'tasks.id')
                    ->where('repliable_type', Task::class),
                ]));

        $this->applySort($query, $filters['sort'] ?? null);

        return $query;
    }

    public function rows(User $user, array $filters = [], int $limit = 25): array
    {
        $rows = $this->query($user, $filters)
            ->take($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $departmentOptions = Department::getCachedOptions();

        return [
            'rows' => $rows->take($limit)->map(fn(Task $task) => $this->presentRow($task, $departmentOptions))->all(),
            'hasMore' => $hasMore,
        ];
    }

    public function summary(int $projectId, int $viewerId): array
    {
        return Cache::remember("task-report:{$projectId}:{$viewerId}", now()->addMinutes(5), function () use ($projectId) {
            $counts = Task::where('project_id', $projectId)
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $total = (int) $counts->sum();
            $done = (int) ($counts['done'] ?? 0);

            $overdue = (int) Task::where('project_id', $projectId)
                ->where('status', '<>', 'done')
                ->where('deadline', '<', now())
                ->count();

            $project = Project::find($projectId, ['id', 'settings']);
            $sla = $project?->setting('sla');
            $deadlineCap = $project?->setting('deadline');

            $atRisk = 0;
            if ($sla !== null || $deadlineCap !== null) {
                $atRisk = (int) Task::where('project_id', $projectId)
                    ->whereNotIn('status', ['done', 'pending'])
                    ->where(function (Builder $q) use ($sla, $deadlineCap) {
                        if ($sla !== null) {
                            $q->orWhereRaw('TIMESTAMPDIFF(HOUR, created_at, NOW()) > ?', [(int) $sla]);
                        }
                        if ($deadlineCap !== null) {
                            $q->orWhere('deadline', '>', Carbon::parse($deadlineCap)->endOfDay());
                        }
                    })
                    ->count();
            }

            return [
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? round($done / $total * 100, 1) : 0.0,
                'by_status' => [
                    'todo' => (int) ($counts['todo'] ?? 0),
                    'in-progress' => (int) ($counts['in-progress'] ?? 0),
                    'pending' => (int) ($counts['pending'] ?? 0),
                    'done' => $done,
                ],
                'overdue' => $overdue,
                'at_risk' => $atRisk,
            ];
        });
    }

    public function filterOptions(int $projectId): array
    {
        $key = sprintf(
            'report:filter_options:%d:t%s:d%s',
            $projectId,
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(TaskDetail::class)
        );

        return Cache::flexible(
            $key,
            [self::FILTER_OPTIONS_FRESH_SECONDS, self::FILTER_OPTIONS_STALE_SECONDS],
            function () use ($projectId) {
                $rows = Task::where('project_id', $projectId)->toBase()->get(['assigned_to', 'user_id']);
                $userIds = collect($rows)->flatMap(fn($t) => array_filter([$t->assigned_to, $t->user_id]))->unique()->all();

                $departmentCodes = TaskDetail::whereHas('task', fn(Builder $q) => $q->where('project_id', $projectId))
                    ->whereNotNull('department_id')
                    ->distinct()
                    ->pluck('department_id');

                $schemes = TaskDetail::whereHas('task', fn(Builder $q) => $q->where('project_id', $projectId))
                    ->whereNotNull('scheme')
                    ->distinct()
                    ->pluck('scheme')
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'assignees' => User::whereIn('id', $userIds)->pluck('name', 'id')->all(),
                    'departments' => Department::getCachedOptions()->only($departmentCodes)->all(),
                    'schemes' => $schemes,
                ];
            }
        );
    }

    public function boardDetailOptions(Closure $taskScope, string $scopeKey): array
    {
        return Cache::remember(
            $this->boardCacheKey('detail_options', $scopeKey),
            now()->addSeconds(ModelCacheVersion::defaultSeconds()),
            function () use ($taskScope) {
                $rows = TaskDetail::whereHas('task', $taskScope)
                    ->where(fn(Builder $q) => $q->whereNotNull('scheme')->orWhereNotNull('unit')->orWhereNotNull('section'))
                    ->get(['scheme', 'unit', 'section']);

                $present = fn(Collection $c) => $c->filter(fn($v) => $v !== null && $v !== '')->unique()->sort()->values()->all();

                return [
                    'schemes' => $present($rows->pluck('scheme')),
                    'units' => $present($rows->pluck('unit')),
                    'sections' => $present($rows->pluck('section')),
                ];
            }
        );
    }

    public function boardSchemeOptions(Closure $taskScope, string $scopeKey): array
    {
        return $this->boardDetailOptions($taskScope, $scopeKey)['schemes'];
    }

    public function boardUnitOptions(Closure $taskScope, string $scopeKey): array
    {
        return $this->boardDetailOptions($taskScope, $scopeKey)['units'];
    }

    public function boardSectionOptions(Closure $taskScope, string $scopeKey): array
    {
        return $this->boardDetailOptions($taskScope, $scopeKey)['sections'];
    }

    public function boardAssigneeOptions(Closure $taskScope, string $scopeKey): array
    {
        return Cache::remember(
            $this->boardCacheKey('assignee_options', $scopeKey),
            now()->addSeconds(ModelCacheVersion::defaultSeconds()),
            function () use ($taskScope) {
                $ids = Task::query()
                    ->tap($taskScope)
                    ->whereNotNull('assigned_to')
                    ->distinct()
                    ->pluck('assigned_to');

                return User::whereIn('id', $ids)->pluck('name', 'id')->all();
            }
        );
    }

    public function boardActionSourceDomainOptions(Closure $taskScope, string $scopeKey): array
    {
        return Cache::remember(
            $this->boardCacheKey('action_source_domain_options', $scopeKey),
            now()->addSeconds(ModelCacheVersion::defaultSeconds()),
            fn() => TaskDetail::whereHas('task', $taskScope)
                ->whereNotNull('action_source_domain')
                ->distinct()
                ->pluck('action_source_domain')
                ->sort()
                ->values()
                ->all()
        );
    }

    public function boardActionSourceOptions(Closure $taskScope, string $scopeKey, ?string $domainFilter = null): array
    {
        return Cache::remember(
            $this->boardCacheKey('action_source_options:' . ($domainFilter ?? '*'), $scopeKey),
            now()->addSeconds(ModelCacheVersion::defaultSeconds()),
            fn() => TaskDetail::whereHas('task', $taskScope)
                ->whereNotNull('action_source')
                ->when($domainFilter, fn(Builder $q) => $q->where('action_source_domain', $domainFilter))
                ->distinct()
                ->pluck('action_source')
                ->sort()
                ->values()
                ->all()
        );
    }

    private function boardCacheKey(string $suffix, string $scopeKey): string
    {
        return sprintf(
            'board:%s:%s:t%s:d%s',
            $suffix,
            $scopeKey,
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(TaskDetail::class)
        );
    }

    public function boardDeadlineCounts(Builder $baseQuery): array
    {
        $base = (clone $baseQuery)
            ->whereNotNull('deadline')
            ->whereNull('archived_at')
            ->where('status', '!=', TaskStatus::Done->value);

        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        $row = (clone $base)->selectRaw('
            SUM(CASE WHEN deadline < ? THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN DATE(deadline) = ? THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN deadline BETWEEN ? AND ? THEN 1 ELSE 0 END) as week
        ', [now()->startOfDay(), $today, $weekStart, $weekEnd])->first();

        return [
            'overdue' => (int)($row->overdue ?? 0),
            'today' => (int)($row->today ?? 0),
            'week' => (int)($row->week ?? 0),
        ];
    }

    public function schemeProgress(User $user, array $filters = []): array
    {
        $tasks = $this->query($user, $filters, withCounts: false)
            ->with('detail:id,task_id,scheme')
            ->get()
            ->filter(fn(Task $task) => filled($task->detail?->scheme));

        return $tasks->groupBy(fn(Task $task) => $task->detail->scheme)
            ->map(fn(Collection $group, string $scheme) => [
                'scheme' => $scheme,
                'done' => $group->where('status', 'done')->count(),
                'total' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    public function attachments(User $user, array $filters = []): array
    {
        return $this->query($user, $filters, withCounts: false)
            ->with('detail:id,task_id,attachments')
            ->get()
            ->filter(fn(Task $task) => filled($task->detail?->attachments))
            ->map(fn(Task $task) => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'attachments' => $task->detail->attachments,
            ])
            ->values()
            ->all();
    }

    public function analyticsInsights(int $projectId): array
    {
        $key = sprintf(
            'report:insights:%d:t%s:d%s:r%s',
            $projectId,
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(TaskDetail::class),
            ModelCacheVersion::version(Reply::class)
        );

        return Cache::flexible($key, [self::FILTER_OPTIONS_FRESH_SECONDS, self::FILTER_OPTIONS_STALE_SECONDS], function () use ($projectId) {
            $doneEvents = $this->doneEvents($projectId);
            $stale = $this->staleBuckets($projectId);
            $total = $this->totalTaskCount($projectId);
            $flow = $this->flowMetrics($projectId, $doneEvents, $stale);
            $risk = $this->riskMetrics($projectId, $doneEvents);
            $people = $this->peopleMetrics($projectId);

            return [
                'flow' => $flow,
                'risk' => $risk,
                'people' => $people,
                'meta' => [
                    'total' => $total,
                    'populated' => [
                        'labels' => $people['labelsDistribution'] !== null,
                        'department' => $people['departmentCompletion'] !== null,
                    ],
                ],
            ];
        });
    }

    private function totalTaskCount(int $projectId): int
    {
        return (int) Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->count();
    }

    private function doneEvents(int $projectId): array
    {
        return DB::table('tasks as t')
            ->joinSub(
                Reply::select('repliable_id', DB::raw('MAX(created_at) as done_at'))
                    ->where('repliable_type', Task::class)
                    ->where('type', TaskActivityType::StatusChange->value)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.to')) = ?", [TaskStatus::Done->value])
                    ->whereIn('repliable_id', fn($q) => $q->select('id')->from('tasks')->where('project_id', $projectId)->whereNull('deleted_at'))
                    ->groupBy('repliable_id'),
                'latest',
                'latest.repliable_id',
                '=',
                't.id'
            )
            ->where('t.project_id', $projectId)
            ->whereNull('t.deleted_at')
            ->whereNull('t.archived_at')
            ->where('t.status', TaskStatus::Done->value)
            ->orderBy('latest.done_at')
            ->get(['latest.done_at', 't.created_at as task_created_at', 't.priority', 't.deadline'])
            ->all();
    }

    private function flowMetrics(int $projectId, array $doneEvents, array $stale): array
    {
        $projectCreatedAt = Project::whereKey($projectId)->value('created_at');
        $ageWeeks = $projectCreatedAt ? (int) floor(now()->diffInDays(Carbon::parse($projectCreatedAt), true) / 7) : 0;
        $doneCount = count($doneEvents);

        $throughput = ($ageWeeks >= 2 && $doneCount >= 3)
            ? $this->throughputSeries($doneEvents, min(8, max(1, $ageWeeks)))
            : null;

        return [
            'throughput' => $throughput,
            'staleDistribution' => $this->staleChart($stale),
            'cycleDistribution' => $doneCount >= 3 ? $this->cycleHistogram($doneEvents) : null,
            'stale' => $stale,
            'medianCycle' => $doneCount >= 3 ? $this->medianCycleDays($doneEvents) : null,
            'doneThisWeek' => $this->doneThisWeekCount($doneEvents),
        ];
    }

    private function staleChart(array $stale): ?array
    {
        $data = [$stale['active'], $stale['idle_7_14'], $stale['idle_14_30'], $stale['idle_30_plus']];
        if (array_sum($data) === 0) {
            return null;
        }

        return [
            'labels' => ['فعال (۷ روز اخیر)', 'بی‌تغیر ۷–۱۴ روز', 'بی‌تغیر ۱۴–۳۰ روز', 'بی‌تغیر +۳۰ روز'],
            'datasets' => [[
                'label' => 'کارها',
                'data' => $data,
                'backgroundColor' => [
                    'var(--md-sys-color-primary-container)',
                    'var(--md-sys-color-secondary-container)',
                    'var(--md-sys-color-tertiary-container)',
                    'var(--md-sys-color-error-container)',
                ],
            ]],
        ];
    }

    private function cycleHistogram(array $doneEvents): array
    {
        $buckets = [0, 0, 0, 0];
        foreach ($doneEvents as $row) {
            $days = Carbon::parse($row->task_created_at)->diffInDays(Carbon::parse($row->done_at));
            if ($days < 3) {
                $buckets[0]++;
            } elseif ($days < 7) {
                $buckets[1]++;
            } elseif ($days < 14) {
                $buckets[2]++;
            } else {
                $buckets[3]++;
            }
        }

        return [
            'labels' => ['۰ تا ۳ روز', '۳ تا ۷ روز', '۷ تا ۱۴ روز', 'بیش از ۱۴ روز'],
            'datasets' => [[
                'label' => 'وظایف',
                'data' => $buckets,
                'backgroundColor' => [
                    'var(--md-sys-color-primary-container)',
                    'var(--md-sys-color-secondary-container)',
                    'var(--md-sys-color-tertiary-container)',
                    'var(--md-sys-color-error-container)',
                ],
            ]],
        ];
    }

    private function throughputSeries(array $doneEvents, int $weeks): array
    {
        $cursor = now()->startOfWeek(Carbon::SATURDAY);
        $weekStarts = [];
        $buckets = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = $cursor->copy()->subWeeks($i);
            $key = $start->toDateString();
            $weekStarts[$key] = $start;
            $buckets[$key] = 0;
        }
        foreach ($doneEvents as $row) {
            $key = Carbon::parse($row->done_at)->startOfWeek(Carbon::SATURDAY)->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key]++;
            }
        }

        return [
            'labels' => array_map(fn(Carbon $start) => toJalali($start, 'j F'), array_values($weekStarts)),
            'datasets' => [[
                'label' => 'تکمیل در هفته',
                'data' => array_values($buckets),
                'borderColor' => 'var(--md-sys-color-primary)',
                'backgroundColor' => 'var(--md-sys-color-primary-container)',
                'fill' => false,
                'tension' => 0.35,
            ]],
        ];
    }

    private function medianCycleDays(array $doneEvents): ?float
    {
        $days = array_map(fn($row) => Carbon::parse($row->task_created_at)->diffInDays(Carbon::parse($row->done_at)), $doneEvents);
        sort($days);
        $n = count($days);
        if ($n === 0) {
            return null;
        }

        return $n % 2 ? (float) $days[intdiv($n, 2)] : ($days[$n / 2 - 1] + $days[$n / 2]) / 2;
    }

    private function doneThisWeekCount(array $doneEvents): int
    {
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);

        return count(array_filter($doneEvents, fn($row) => Carbon::parse($row->done_at)->greaterThanOrEqualTo($weekStart)));
    }

    private function staleBuckets(int $projectId): array
    {
        $row = Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Pending->value])
            ->selectRaw('
                SUM(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND updated_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 1 ELSE 0 END) AS idle_7_14,
                SUM(CASE WHEN updated_at < DATE_SUB(NOW(), INTERVAL 14 DAY) AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS idle_14_30,
                SUM(CASE WHEN updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS idle_30_plus
            ')
            ->first();

        return [
            'active' => (int) ($row->active ?? 0),
            'idle_7_14' => (int) ($row->idle_7_14 ?? 0),
            'idle_14_30' => (int) ($row->idle_14_30 ?? 0),
            'idle_30_plus' => (int) ($row->idle_30_plus ?? 0),
        ];
    }

    private function riskMetrics(int $projectId, array $doneEvents): array
    {
        $matrixRows = DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->selectRaw('priority, status, count(*) as aggregate')
            ->groupBy('priority', 'status')
            ->get();

        $atRisk = DB::table('tasks')
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->whereNotIn('status', [TaskStatus::Done->value])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3))
            ->selectRaw('priority, count(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        return [
            'matrix' => $this->buildMatrix($matrixRows),
            'atRiskByPriority' => $this->priorityDoughnut($atRisk, 'در معرض ریسک'),
            'horizon' => $this->deadlineHorizon($projectId),
            'adherence' => $this->adherence($doneEvents),
            'atRiskCount' => (int) $atRisk->sum(),
        ];
    }

    private function buildMatrix(Collection $rows): array
    {
        $priorities = TaskPriority::cases();
        $statuses = TaskStatus::cases();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->priority][$row->status] = (int) $row->aggregate;
        }
        $cells = [];
        $fire = [];
        foreach ($priorities as $p) {
            $rowCells = [];
            foreach ($statuses as $s) {
                $count = (int) ($map[$p->value][$s->value] ?? 0);
                $rowCells[] = $count;
                if ($p === TaskPriority::Urgent && in_array($s->value, [TaskStatus::Todo->value, TaskStatus::InProgress->value], true) && $count > 0) {
                    $fire[] = [$p->value, $s->value];
                }
            }
            $cells[] = $rowCells;
        }

        return [
            'priorities' => array_map(fn(TaskPriority $p) => ['value' => $p->value, 'label' => $p->getLabel()], $priorities),
            'statuses' => array_map(fn(TaskStatus $s) => ['value' => $s->value, 'label' => $s->getLabel()], $statuses),
            'cells' => $cells,
            'fire' => $fire,
        ];
    }

    private function priorityDoughnut(Collection $counts, string $label): array
    {
        $colors = [
            'low' => 'var(--md-sys-color-surface-container-highest)',
            'medium' => 'var(--md-sys-color-secondary-container)',
            'high' => 'var(--md-sys-color-tertiary-container)',
            'urgent' => 'var(--md-sys-color-error-container)',
        ];
        $labels = $data = $background = $values = [];
        foreach (TaskPriority::cases() as $priority) {
            $count = (int) ($counts[$priority->value] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $labels[] = $priority->getLabel();
            $data[] = $count;
            $background[] = $colors[$priority->value];
            $values[] = $priority->value;
        }

        return ['labels' => $labels, 'values' => $values, 'datasets' => [['label' => $label, 'data' => $data, 'backgroundColor' => $background]]];
    }

    private function deadlineHorizon(int $projectId): array
    {
        $weekStart = now()->startOfWeek(Carbon::SATURDAY);
        $thisWeekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $nextWeekStart = $weekStart->copy()->addWeek();
        $nextWeekEnd = $nextWeekStart->copy()->addDays(6)->endOfDay();

        $row = Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotIn('status', [TaskStatus::Done->value])
            ->selectRaw('
                SUM(CASE WHEN deadline IS NULL THEN 1 ELSE 0 END) AS no_deadline,
                SUM(CASE WHEN deadline IS NOT NULL AND deadline < ? THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN deadline BETWEEN ? AND ? THEN 1 ELSE 0 END) AS this_week,
                SUM(CASE WHEN deadline BETWEEN ? AND ? THEN 1 ELSE 0 END) AS next_week,
                SUM(CASE WHEN deadline > ? THEN 1 ELSE 0 END) AS later
            ', [now(), now(), $thisWeekEnd, $nextWeekStart, $nextWeekEnd, $nextWeekEnd])
            ->first();

        $labels = ['سررسید گذشته', 'این هفته', 'هفتهٔ بعد', 'بعدتر', 'بدون مهلت'];
        $data = [
            (int) ($row->overdue ?? 0),
            (int) ($row->this_week ?? 0),
            (int) ($row->next_week ?? 0),
            (int) ($row->later ?? 0),
            (int) ($row->no_deadline ?? 0),
        ];
        $background = [
            'var(--md-sys-color-error-container)',
            'var(--md-sys-color-primary-container)',
            'var(--md-sys-color-secondary-container)',
            'var(--md-sys-color-tertiary-container)',
            'var(--md-sys-color-surface-container-highest)',
        ];

        $values = ['overdue', 'week', null, null, null];

        return ['labels' => $labels, 'values' => $values, 'datasets' => [['label' => 'مهلت‌ها', 'data' => $data, 'backgroundColor' => $background]]];
    }

    private function adherence(array $doneEvents): ?array
    {
        $withDeadline = array_filter($doneEvents, fn($row) => $row->deadline !== null);
        if (count($withDeadline) < 3) {
            return null;
        }
        $onTime = 0;
        foreach ($withDeadline as $row) {
            if (Carbon::parse($row->done_at)->lessThanOrEqualTo(Carbon::parse($row->deadline))) {
                $onTime++;
            }
        }

        return ['onTime' => $onTime, 'total' => count($withDeadline)];
    }

    private function peopleMetrics(int $projectId): array
    {
        $rows = Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->selectRaw('assigned_to, status, count(*) as aggregate')
            ->groupBy('assigned_to', 'status')
            ->get();
        $names = $this->assigneeNames($rows);
        $openRows = $rows->where('status', '!=', TaskStatus::Done->value)->values();

        $orphanCount = (int) Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotIn('status', [TaskStatus::Done->value])
            ->whereNull('assigned_to')
            ->whereDoesntHave('detail', fn(Builder $d) => $d->whereJsonLength('collaborators', '>', 0))
            ->count();

        return [
            'perAssignee' => $this->perAssigneeStack($openRows, $names),
            'doneByAssignee' => $this->doneByAssignee($rows, $names),
            'delegationRatio' => $this->delegationRatio($projectId),
            'orphanCount' => $orphanCount,
            'labelsDistribution' => $this->labelsDistribution($projectId),
            'departmentCompletion' => $this->departmentCompletion($projectId),
        ];
    }

    private function doneByAssignee(Collection $rows, array $names): ?array
    {
        $totals = [];
        foreach ($rows as $row) {
            if ($row->status === TaskStatus::Done->value && $row->assigned_to !== null) {
                $totals[$row->assigned_to] = ($totals[$row->assigned_to] ?? 0) + (int) $row->aggregate;
            }
        }
        if ($totals === []) {
            return null;
        }
        arsort($totals);

        $ids = array_keys($totals);

        return [
            'labels' => array_map(fn($id) => $names[$id] ?? (string) $id, $ids),
            'values' => array_map(fn($id) => (string) $id, $ids),
            'datasets' => [[
                'label' => 'انجام‌شده',
                'data' => array_map(fn($id) => $totals[$id], $ids),
                'backgroundColor' => 'var(--md-sys-color-primary-container)',
            ]],
        ];
    }

    private function assigneeNames(Collection $rows): array
    {
        $ids = $rows->pluck('assigned_to')->filter()->unique()->values()->all();

        return $ids === [] ? [] : User::whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    private function perAssigneeStack(Collection $rows, array $names): array
    {
        $map = [];
        $assigneeIds = [];
        $hasOrphan = false;
        foreach ($rows as $r) {
            $id = $r->assigned_to;
            if ($id === null) {
                $hasOrphan = true;
            } else {
                $assigneeIds[$id] = true;
            }
            $map[$id ?? ''][$r->status] = (int) $r->aggregate;
        }
        $ids = array_keys($assigneeIds);
        $labels = array_map(fn($id) => $names[$id] ?? (string) $id, $ids);
        $values = array_map(fn($id) => (string) $id, $ids);
        if ($hasOrphan) {
            $labels[] = 'بی‌مسئول';
            $values[] = null;
        }
        $statusColors = [
            'todo' => 'var(--md-sys-color-primary-container)',
            'in-progress' => 'var(--md-sys-color-secondary-container)',
            'pending' => 'var(--md-sys-color-error-container)',
        ];
        $datasets = [];
        foreach (TaskStatus::cases() as $s) {
            if ($s->value === TaskStatus::Done->value) {
                continue;
            }
            $data = [];
            foreach ($ids as $id) {
                $data[] = (int) ($map[$id][$s->value] ?? 0);
            }
            if ($hasOrphan) {
                $data[] = (int) ($map[''][$s->value] ?? 0);
            }
            $datasets[] = ['label' => $s->getLabel(), 'data' => $data, 'backgroundColor' => $statusColors[$s->value] ?? 'var(--md-sys-color-surface-container-highest)'];
        }

        return ['labels' => $labels, 'values' => $values, 'datasets' => $datasets];
    }

    private function delegationRatio(int $projectId): float
    {
        $row = Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN assigned_to IS NOT NULL AND user_id IS NOT NULL AND assigned_to <> user_id THEN 1 ELSE 0 END) AS delegated')
            ->first();
        $total = (int) ($row->total ?? 0);
        if ($total === 0) {
            return 0.0;
        }

        return round((int) ($row->delegated ?? 0) / $total * 100, 1);
    }

    private function labelsDistribution(int $projectId): ?array
    {
        $rows = Task::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotNull('labels')
            ->pluck('labels');
        $counts = [];
        foreach ($rows as $labels) {
            foreach ((array) $labels as $l) {
                if ($l !== null && $l !== '') {
                    $counts[$l] = ($counts[$l] ?? 0) + 1;
                }
            }
        }
        arsort($counts);
        if (count($counts) < 2) {
            return null;
        }
        $top = array_slice($counts, 0, 8, true);
        $palette = ['#8b5cf6', '#6366f1', '#ef4444', '#f59e0b', '#0ea5e9', '#10b981', '#3b82f6', '#64748b'];

        return [
            'labels' => array_keys($top),
            'values' => array_keys($top),
            'datasets' => [['label' => 'برچسب‌ها', 'data' => array_values($top), 'backgroundColor' => array_slice($palette, 0, count($top))]],
        ];
    }

    private function departmentCompletion(int $projectId): ?array
    {
        $rows = DB::table('tasks')
            ->leftJoin('task_details', 'task_details.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $projectId)
            ->whereNull('tasks.deleted_at')
            ->whereNull('tasks.archived_at')
            ->whereNotNull('task_details.department_id')
            ->selectRaw('task_details.department_id as department_id, tasks.status, count(*) as aggregate')
            ->groupBy('department_id', 'tasks.status')
            ->get();

        $byDept = [];
        foreach ($rows as $r) {
            $code = $r->department_id;
            $byDept[$code] ??= ['done' => 0, 'remaining' => 0];
            if ($r->status === TaskStatus::Done->value) {
                $byDept[$code]['done'] += (int) $r->aggregate;
            } else {
                $byDept[$code]['remaining'] += (int) $r->aggregate;
            }
        }
        if (empty($byDept)) {
            return null;
        }
        $options = Department::getCachedOptions();
        uasort($byDept, fn($a, $b) => ($b['done'] + $b['remaining']) <=> ($a['done'] + $a['remaining']));
        $labels = $done = $remaining = $values = [];
        foreach ($byDept as $code => $c) {
            $labels[] = $options->get($code, (string) $code);
            $done[] = $c['done'];
            $remaining[] = $c['remaining'];
            $values[] = (string) $code;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'datasets' => [
                ['label' => 'انجام‌شده', 'data' => $done, 'backgroundColor' => 'var(--md-sys-color-tertiary-container)'],
                ['label' => 'باقی‌مانده', 'data' => $remaining, 'backgroundColor' => 'var(--md-sys-color-surface-container-highest)'],
            ],
        ];
    }

    private function presentRow(Task $task, ?Collection $departmentOptions = null): array
    {
        $departmentOptions ??= Department::getCachedOptions();

        $checklist = $task->detail?->checklist ?? [];
        $checklistTotal = count($checklist);
        $checklistDone = count(array_filter($checklist, fn($item) => $item['done'] ?? false));

        $customSchema = $task->project?->setting('custom_schema') ?? [];
        $metaChips = [];
        foreach (($task->detail?->meta ?? []) as $key => $value) {
            if (!is_scalar($value) || $value === null || $value === '') continue;
            $metaChips[] = [
                'key' => (string) $key,
                'label' => (string) ($customSchema[$key]['label'] ?? $key),
                'value' => (string) $value,
            ];
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'project_name' => $task->project?->name,
            'department' => $task->detail?->department_id,
            'department_label' => $task->detail?->department_id
                ? $departmentOptions->get($task->detail->department_id, $task->detail->department_id)
                : null,
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'status' => $task->status,
            'priority' => $task->priority?->value,
            'labels' => $task->labels ?? [],
            'deadline' => $task->deadline?->toIso8601String(),
            'deadline_formatted' => $task->deadline_formatted,
            'created_formatted' => $task->created_formatted,
            'progress_percent' => $this->progressPercent($task),
            'last_activity_at' => $task->last_activity_at,
            'replies_count' => (int) $task->replies_count,
            'attachments_count' => count($task->detail?->attachments ?? []),
            'checklist' => ['done' => $checklistDone, 'total' => $checklistTotal],
            'urgency' => $task->urgency_state,
            'ticket_id' => $task->ticket_id,
            'meta_chips' => $metaChips,
        ];
    }

    public function progressPercent(Task $task): int
    {
        return $task->progress_percent;
    }

    private function applySort(Builder $query, ?array $sort): void
    {
        $field = in_array($sort['field'] ?? null, self::SORT_FIELDS, true) ? $sort['field'] : 'deadline';
        $dir = ($sort['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($field === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low') " . $dir);
        } elseif (in_array($field, ['deadline', 'last_activity_at'], true)) {
            $query->orderByRaw($field . ' IS NULL, ' . $field . ' ' . $dir);
        } else {
            $query->orderBy($field, $dir);
        }
    }
}