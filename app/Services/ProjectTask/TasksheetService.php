<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TasksheetService
{
    private const RELEVANT_TYPES = [
        TaskActivityType::StatusChange->value,
        TaskActivityType::Approval->value,
        TaskActivityType::Assignment->value,
        TaskActivityType::Comment->value,
    ];

    private const FRESH_SECONDS = 60;
    private const STALE_SECONDS = 300;

    private const PERSONAL_LABEL = 'وظیفه شخصی';
    private const MASKED_TASK = 'وظیفهٔ محدود';
    private const MASKED_PROJECT = 'پروژهٔ محدود';

    public static function baselineWindow(Carbon $start, Carbon $end): array
    {
        $baselineEnd = $start->copy()->subSecond();

        return ['start' => $baselineEnd->copy()->subSeconds((int) $start->diffInSeconds($end)), 'end' => $baselineEnd];
    }

    public function report(User $subject, Carbon $start, Carbon $end, ?User $viewer = null): array
    {
        $key = sprintf(
            'tasksheet2:%d:%s:%s:t%s:d%s:r%s',
            $subject->id,
            $start->toDateString(),
            $end->toDateString(),
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(TaskDetail::class),
            ModelCacheVersion::version(Reply::class)
        );

        $report = Cache::flexible($key, [self::FRESH_SECONDS, self::STALE_SECONDS], fn() => $this->buildReport($subject, $start, $end));

        return $this->isRestricted($subject, $viewer) ? $this->restrict($report, $viewer) : $report;
    }

    public function activityFeed(User $subject, Carbon $start, Carbon $end, int $page = 1, int $perPage = 30, ?User $viewer = null): array
    {
        $restricted = $this->isRestricted($subject, $viewer);

        $replies = $this->scopedEvents($subject->id, $this->ownedTaskIds($subject->id, $start, $end), $start, $end)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get(['id', 'repliable_id', 'user_id', 'type', 'payload', 'body', 'created_at']);

        if ($replies->isEmpty()) {
            return ['days' => [], 'has_more' => false, 'next_page' => null];
        }

        $hasMore = $replies->count() > $perPage;
        $replies = $replies->take($perPage);

        $tasks = Task::withTrashed()
            ->whereKey($replies->pluck('repliable_id')->unique())
            ->with('detail:id,task_id,state')
            ->get(['id', 'title', 'project_id', 'deleted_at'])
            ->keyBy('id');

        $visible = $restricted ? $this->visibleProjectIds($tasks->pluck('project_id'), $viewer) : collect();
        $logger = app(ActivityLogger::class);

        $items = $replies->map(function (Reply $reply) use ($tasks, $visible, $restricted, $logger) {
            $task = $tasks->get($reply->repliable_id);
            $masked = $restricted && $task && !($task->project_id && $visible->has($task->project_id));
            $reply->setRelation('repliable', $task);
            $rendered = $logger->render($reply);

            if ($restricted && in_array($reply->type, [TaskActivityType::Comment, TaskActivityType::Attachment], true)) {
                $rendered['body'] = null;
            }

            return [
                'reply_id' => $reply->id,
                'task_id' => $reply->repliable_id,
                'task_title' => $masked ? ($task->project_id ? self::MASKED_TASK : self::PERSONAL_LABEL) : $task?->title,
                'task_trashed' => (bool) $task?->trashed(),
                'masked' => $masked,
                'actor_name' => $reply->user?->name ?? 'سیستم',
                ...$rendered,
                'created_at' => $reply->created_at->toIso8601String(),
            ];
        });

        $days = $items
            ->groupBy(fn(array $item) => Carbon::parse($item['created_at'])->setTimezone(config('app.timezone'))->toDateString())
            ->map(fn(Collection $group, string $date) => [
                'date' => $date,
                'date_jalali' => Carbon::parse($date)->isToday()
                    ? 'امروز'
                    : (Carbon::parse($date)->isYesterday() ? 'دیروز' : toJalali($date, 'j F Y')),
                'items' => $group->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'days' => $days,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        ];
    }

    private function isRestricted(User $subject, ?User $viewer): bool
    {
        return $viewer !== null && $viewer->id !== $subject->id;
    }

    private function visibleProjectIds(Collection $projectIds, User $viewer): Collection
    {
        $ids = $projectIds->filter()->unique()->values();

        return $ids->isEmpty()
            ? collect()
            : Project::withTrashed()->visibleTo($viewer)->whereIn('id', $ids)->pluck('id')->flip();
    }

    private function restrict(array $report, User $viewer): array
    {
        $visible = $this->visibleProjectIds(collect($report['projects'])->pluck('project_id'), $viewer);

        $maskTask = fn(array $task): array => $task['project_id'] && $visible->has($task['project_id'])
            ? $task
            : [...$task, 'title' => $task['project_id'] ? self::MASKED_TASK : self::PERSONAL_LABEL, 'masked' => true];

        $report['projects'] = array_map(function (array $project) use ($visible, $maskTask) {
            $allowed = $visible->has($project['project_id']);

            return [
                ...$project,
                'project_name' => $allowed ? $project['project_name'] : self::MASKED_PROJECT,
                'masked' => !$allowed,
                'tasks' => array_map($maskTask, $project['tasks']),
            ];
        }, $report['projects']);

        if ($report['standalone']) {
            $report['standalone']['tasks'] = array_map($maskTask, $report['standalone']['tasks']);
        }

        foreach ($report['highlights'] as $kind => $highlight) {
            $report['highlights'][$kind] = $highlight ? $maskTask($highlight) : null;
        }

        return $report;
    }

    private function buildReport(User $subject, Carbon $start, Carbon $end): array
    {
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        ['owned' => $owned, 'events' => $events, 'tasks' => $tasks, 'mine' => $mine] = $this->gather($subject->id, $start, $end);

        if ($tasks->isEmpty()) {
            return $this->zeroStateReport($start, $end, $days);
        }

        $approvals = $this->approvalsReceived($events, $tasks, $owned, $mine, $subject->id);
        $metrics = $this->scorecardMetrics($mine, $approvals);
        $baselineMetrics = $this->baselineMetrics($subject, $start, $end);
        $hasBaseline = $baselineMetrics !== null && $baselineMetrics['completed'] >= 3;

        [$stillOverdue, $inProgress, $upcomingDeadline, $flags] = $this->liveCounts($tasks, $owned);

        $groupTasks = $tasks->filter(fn(Task $t) => $mine->has($t->id) || isset($flags[$t->id]));
        $assigned = $owned->merge($mine->keys())->unique()->count();

        return [
            'window' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'days' => $days],
            'scorecard' => [
                'completed' => [
                    'value' => $metrics['completed'],
                    'previous' => $hasBaseline ? $baselineMetrics['completed'] : null,
                    'delta_percent' => $hasBaseline ? $this->deltaPercent($metrics['completed'], $baselineMetrics['completed']) : null,
                ],
                'on_time_percent' => [
                    'value' => $metrics['onTimePercent'],
                    'sample' => $metrics['sample'],
                    'previous' => $hasBaseline ? $baselineMetrics['onTimePercent'] : null,
                    'delta_percent' => $hasBaseline ? $this->deltaPoints($metrics['onTimePercent'], $baselineMetrics['onTimePercent']) : null,
                ],
                'cycle_time_days' => [
                    'median' => $metrics['median'],
                    'avg' => $metrics['avg'],
                    'previous_median' => $hasBaseline ? $baselineMetrics['median'] : null,
                    'delta_percent' => $hasBaseline ? $this->deltaPercent($metrics['median'], $baselineMetrics['median']) : null,
                ],
                'approvals_received' => [
                    'value' => $metrics['approvalsReceived'],
                    'previous' => $hasBaseline ? $baselineMetrics['approvalsReceived'] : null,
                    'delta_percent' => $hasBaseline ? $this->deltaPercent($metrics['approvalsReceived'], $baselineMetrics['approvalsReceived']) : null,
                ],
                'still_overdue' => $stillOverdue,
                'in_progress' => $inProgress,
                'upcoming_deadline' => $upcomingDeadline,
            ],
            'has_baseline' => $hasBaseline,
            'weekly_totals' => $this->weeklyTotals($mine, $start, $end),
            'narrative' => $this->narrative($metrics['completed'], $assigned, $metrics['onTimePercent'], $metrics['avg'], $metrics['approvalsReceived'], $days, $this->reactionsReceived($events, $subject->id)),
            'highlights' => $this->highlights($mine, $events, $subject->id),
            'projects' => $this->projectGroups($groupTasks, $mine, $flags, $subject->id),
            'standalone' => $this->standaloneGroup($groupTasks, $mine, $flags),
        ];
    }

    private function baselineMetrics(User $subject, Carbon $start, Carbon $end): ?array
    {
        $window = self::baselineWindow($start, $end);

        ['owned' => $owned, 'events' => $events, 'tasks' => $tasks, 'mine' => $mine] = $this->gather($subject->id, $window['start'], $window['end']);

        if ($tasks->isEmpty()) {
            return null;
        }

        return $this->scorecardMetrics($mine, $this->approvalsReceived($events, $tasks, $owned, $mine, $subject->id));
    }

    private function gather(int $userId, Carbon $start, Carbon $end): array
    {
        $owned = $this->ownedTaskIds($userId, $start, $end);
        $events = $this->scopedEvents($userId, $owned, $start, $end)
            ->get(['id', 'repliable_id', 'user_id', 'type', 'payload', 'reactions', 'created_at']);
        $tasks = $this->loadTasks($owned->merge($events->pluck('repliable_id'))->unique()->values());

        return [
            'owned' => $owned,
            'events' => $events,
            'tasks' => $tasks,
            'mine' => $this->completions($events, $tasks, $userId, $start),
        ];
    }

    private function ownedTaskIds(int $userId, Carbon $start, Carbon $end): Collection
    {
        return Task::withTrashed()
            ->ownedBy($userId)
            ->where('created_at', '<=', $end)
            ->where(fn(Builder $q) => $q->where('status', '!=', TaskStatus::Done->value)
                ->orWhereNull('completed_at')
                ->orWhere('completed_at', '>=', $start))
            ->pluck('id');
    }

    private function scopedEvents(int $userId, Collection $ownedIds, Carbon $start, Carbon $end): Builder
    {
        return Reply::where('repliable_type', Task::class)
            ->whereIn('type', self::RELEVANT_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->where(fn(Builder $q) => $q->where('user_id', $userId)
                ->orWhere('payload->owner', $userId)
                ->when($ownedIds->isNotEmpty(), fn(Builder $q) => $q->orWhereIn('repliable_id', $ownedIds)));
    }

    private function loadTasks(Collection $taskIds): Collection
    {
        return Task::withTrashed()
            ->whereKey($taskIds)
            ->with([
                'project' => fn($q) => $q->withTrashed()->select(['id', 'name', 'owner_id', 'member_ids', 'settings', 'deleted_at']),
                'detail:id,task_id,department_id,collaborators,state',
            ])
            ->get(['id', 'title', 'status', 'priority', 'deadline', 'project_id', 'assigned_to', 'user_id', 'created_at', 'completed_at', 'archived_at', 'deleted_at'])
            ->keyBy('id');
    }

    private function completions(Collection $events, Collection $tasks, int $userId, Carbon $start): Collection
    {
        $mine = $events
            ->filter(fn(Reply $e) => $e->type === TaskActivityType::StatusChange && ($e->payload['to'] ?? null) === TaskStatus::Done->value)
            ->groupBy('repliable_id')
            ->map(fn(Collection $group) => $group->sortBy([['created_at', 'asc'], ['id', 'asc']])->last())
            ->filter(fn(Reply $e) => ($task = $tasks->get((int) $e->repliable_id)) && $this->creditedTo($e, $task) === $userId);

        if ($mine->isEmpty()) {
            return collect();
        }

        $reopened = Reply::where('repliable_type', Task::class)
            ->whereIn('repliable_id', $mine->keys())
            ->where('type', TaskActivityType::StatusChange->value)
            ->where('payload->from', TaskStatus::Done->value)
            ->where('created_at', '>=', $start)
            ->get(['id', 'repliable_id', 'created_at'])
            ->groupBy('repliable_id');

        return $mine
            ->reject(fn(Reply $done, int $taskId) => ($reopened->get($taskId) ?? collect())->contains(
                fn(Reply $r) => $r->created_at->greaterThan($done->created_at) || ($r->created_at->equalTo($done->created_at) && $r->id > $done->id)
            ))
            ->map(function (Reply $event, int $taskId) use ($tasks) {
                $task = $tasks->get($taskId);

                return [
                    'task_id' => $taskId,
                    'task' => $task,
                    'event' => $event,
                    'cycle_time_days' => (float) $task->created_at->diffInDays($event->created_at),
                    'same_day' => $task->created_at->isSameDay($event->created_at),
                    'on_time' => $task->deadline ? $event->created_at->lessThanOrEqualTo($task->deadline) : null,
                ];
            });
    }

    private function creditedTo(Reply $event, Task $task): int
    {
        return (int) ($event->payload['owner'] ?? $task->assigned_to ?? $task->user_id);
    }

    private function approvalsReceived(Collection $events, Collection $tasks, Collection $owned, Collection $mine, int $userId): int
    {
        $ownedSet = $owned->flip();

        return $events->filter(function (Reply $e) use ($tasks, $ownedSet, $mine, $userId) {
            if ($e->type !== TaskActivityType::Approval || ($e->payload['approved'] ?? false) !== true || !empty($e->payload['system'])) {
                return false;
            }

            $taskId = (int) $e->repliable_id;
            $task = $tasks->get($taskId);

            if (!$task || $e->user_id === null || (int) $e->user_id === $userId || !$task->project?->charter()->requiresApproval()) {
                return false;
            }

            return $mine->has($taskId) || ($ownedSet->has($taskId) && (int) ($task->assigned_to ?? $task->user_id) === $userId);
        })->count();
    }

    private function reactionsReceived(Collection $events, int $userId): int
    {
        return $events
            ->filter(fn(Reply $e) => $e->type === TaskActivityType::Comment && (int) $e->user_id === $userId)
            ->sum(fn(Reply $e) => collect($e->reactions ?? [])->filter(fn($r) => (int) ($r['user_id'] ?? 0) !== $userId)->count());
    }

    private function liveCounts(Collection $tasks, Collection $owned): array
    {
        $ownedSet = $owned->flip();
        $today = now()->startOfDay();
        $now = now();
        $upcomingEnd = $now->copy()->addDays(7);
        $counts = [0, 0, 0];
        $flags = [];

        foreach ($tasks as $task) {
            if ($task->trashed() || $task->archived_at || $task->isSuspended()
                || in_array($task->status, TaskMetrics::SETTLED_STATUSES, true)) {
                continue;
            }

            $isOwned = $ownedSet->has($task->id);

            if ($task->deadline?->lt($today)) {
                $counts[0] += (int) $isOwned;
                $flags[$task->id] = true;
            }
            if ($task->status === TaskStatus::InProgress->value) {
                $counts[1] += (int) $isOwned;
                $flags[$task->id] = true;
            }
            if ($isOwned && $task->deadline?->between($now, $upcomingEnd)) {
                $counts[2]++;
            }
        }

        return [...$counts, $flags];
    }

    private function scorecardMetrics(Collection $mine, int $approvalsReceived): array
    {
        $withDeadline = $mine->filter(fn(array $c) => $c['on_time'] !== null);
        $onTimeCount = $withDeadline->filter(fn(array $c) => $c['on_time'] === true)->count();
        $sample = $withDeadline->count();
        $cycleDays = $mine->pluck('cycle_time_days')->sort()->values();

        return [
            'completed' => $mine->count(),
            'onTimePercent' => $sample > 0 ? round($onTimeCount / $sample * 100, 1) : null,
            'sample' => $sample,
            'median' => $this->median($cycleDays),
            'avg' => $cycleDays->isNotEmpty() ? round($cycleDays->avg(), 1) : null,
            'approvalsReceived' => $approvalsReceived,
        ];
    }

    private function median(Collection $sorted): ?float
    {
        $n = $sorted->count();

        if ($n === 0) {
            return null;
        }

        $values = $sorted->values();

        return $n % 2 ? (float) $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
    }

    private function deltaPercent(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function deltaPoints(?float $current, ?float $previous): ?float
    {
        return $current === null || $previous === null ? null : round($current - $previous, 1);
    }

    private function weeklyTotals(Collection $mine, Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $cursor = $start->copy()->startOfWeek(Carbon::SATURDAY);

        while ($cursor->lte($end)) {
            $weeks[$cursor->toDateString()] = 0;
            $cursor->addWeek();
        }

        foreach ($mine as $completion) {
            $key = $completion['event']->created_at->copy()->startOfWeek(Carbon::SATURDAY)->toDateString();
            if (isset($weeks[$key])) {
                $weeks[$key]++;
            }
        }

        return array_values($weeks);
    }

    private function highlights(Collection $mine, Collection $events, int $userId): array
    {
        if ($mine->isEmpty()) {
            return ['hardest_close' => null, 'fastest_turnaround' => null, 'most_collaborated' => null];
        }

        $comments = $events
            ->filter(fn(Reply $e) => $e->type === TaskActivityType::Comment && (int) $e->user_id !== $userId)
            ->countBy('repliable_id');

        $score = fn(array $c) => ($c['task']->priority?->tier() ?? 0) * $c['cycle_time_days'];

        $hardest = $mine->sort(fn($a, $b) => [$score($b), $a['task_id']] <=> [$score($a), $b['task_id']])->first();
        $fastest = $mine->reject(fn(array $c) => $c['same_day'])
            ->sort(fn($a, $b) => [$a['cycle_time_days'], $a['task_id']] <=> [$b['cycle_time_days'], $b['task_id']])
            ->first();
        $collaborated = $mine
            ->map(fn(array $c) => $c + ['comments' => $comments->get($c['task_id'], 0)])
            ->filter(fn(array $c) => $c['comments'] > 0)
            ->sort(fn($a, $b) => [$b['comments'], $a['task_id']] <=> [$a['comments'], $b['task_id']])
            ->first();

        return [
            'hardest_close' => $this->highlightRow($hardest),
            'fastest_turnaround' => $fastest ? $this->highlightRow($fastest) : null,
            'most_collaborated' => $collaborated ? [...$this->highlightRow($collaborated), 'comments_count' => $collaborated['comments']] : null,
        ];
    }

    private function highlightRow(array $completion): array
    {
        return [
            'task_id' => $completion['task_id'],
            'title' => $completion['task']->title,
            'priority' => $completion['task']->priority?->value,
            'cycle_time_days' => $completion['cycle_time_days'],
            'project_id' => $completion['task']->project_id,
            'trashed' => $completion['task']->trashed(),
        ];
    }

    private function projectGroups(Collection $groupTasks, Collection $mine, array $flags, int $userId): array
    {
        return $groupTasks->whereNotNull('project_id')
            ->groupBy('project_id')
            ->map(function (Collection $tasks) use ($mine, $flags, $userId) {
                $project = $tasks->first()->project;
                $role = match (true) {
                    (int) $project?->owner_id === $userId => 'owner',
                    in_array($userId, array_map('intval', $project?->member_ids ?? []), true) => 'member',
                    default => 'collaborator',
                };

                return [
                    'project_id' => $project?->id,
                    'project_name' => $project?->name,
                    'is_archived' => (bool) $project?->trashed(),
                    'role' => $role,
                    ...$this->groupStats($tasks, $mine, $flags),
                ];
            })
            ->values()
            ->all();
    }

    private function standaloneGroup(Collection $groupTasks, Collection $mine, array $flags): ?array
    {
        $tasks = $groupTasks->whereNull('project_id');

        return $tasks->isEmpty() ? null : $this->groupStats($tasks, $mine, $flags);
    }

    private function groupStats(Collection $tasks, Collection $mine, array $flags): array
    {
        $completed = 0;
        $onTimeCount = 0;
        $withDeadlineCount = 0;
        $stillOverdue = 0;
        $inProgress = 0;
        $drilldown = [];
        $today = now()->startOfDay();

        foreach ($tasks as $task) {
            $completion = $mine->get($task->id);
            $live = isset($flags[$task->id]);
            $isInProgress = $live && $task->status === TaskStatus::InProgress->value;
            $isOverdue = $live && $task->deadline?->lt($today);

            if ($completion) {
                $completed++;
                if ($completion['on_time'] !== null) {
                    $withDeadlineCount++;
                    if ($completion['on_time'] === true) {
                        $onTimeCount++;
                    }
                }
            }

            $stillOverdue += (int) $isOverdue;
            $inProgress += (int) $isInProgress;

            $drilldown[] = $this->taskRow($task, $completion);
        }

        return [
            'completed' => $completed,
            'on_time_percent' => $withDeadlineCount > 0 ? round($onTimeCount / $withDeadlineCount * 100, 1) : null,
            'still_overdue' => $stillOverdue,
            'in_progress' => $inProgress,
            'tasks' => $drilldown,
        ];
    }

    private function taskRow(Task $task, ?array $completion): array
    {
        return [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'trashed' => $task->trashed(),
            'priority' => $task->priority?->value,
            'status' => $task->status,
            'deadline' => $task->deadline?->toIso8601String(),
            'completed_at' => $completion ? $completion['event']->created_at->toIso8601String() : $task->completed_at?->toIso8601String(),
            'cycle_time_days' => $completion['cycle_time_days'] ?? null,
            'on_time' => $completion['on_time'] ?? null,
        ];
    }

    private function narrative(int $completed, int $assigned, ?float $onTimePercent, ?float $avgCycle, int $approvalsReceived, int $days, int $reactions = 0): string
    {
        $onTime = $onTimePercent !== null ? convertToPersian(number_format($onTimePercent, 0)) : '—';
        $avg = $avgCycle !== null ? convertToPersian(number_format($avgCycle, 1)) : '—';
        $reactionsText = $reactions > 0 ? ' و ' . convertToPersian($reactions) . ' واکنش از همکاران گرفت' : '';

        return 'در بازهٔ ' . convertToPersian($days) . ' روزه، ' . convertToPersian($completed) . ' از ' . convertToPersian($assigned)
            . " وظیفهٔ محول‌شده را تکمیل کرد (٪{$onTime} به‌موقع)، میانگین زمان انجام {$avg} روز و " . convertToPersian($approvalsReceived)
            . ' تأیید مدیر دریافت کرد' . $reactionsText . '.';
    }

    private function zeroStateReport(Carbon $start, Carbon $end, int $days): array
    {
        return [
            'window' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'days' => $days],
            'scorecard' => [
                'completed' => ['value' => 0, 'previous' => null, 'delta_percent' => null],
                'on_time_percent' => ['value' => null, 'sample' => 0, 'previous' => null, 'delta_percent' => null],
                'cycle_time_days' => ['median' => null, 'avg' => null, 'previous_median' => null, 'delta_percent' => null],
                'approvals_received' => ['value' => 0, 'previous' => null, 'delta_percent' => null],
                'still_overdue' => 0,
                'in_progress' => 0,
                'upcoming_deadline' => 0,
            ],
            'has_baseline' => false,
            'weekly_totals' => [],
            'narrative' => $this->narrative(0, 0, null, null, 0, $days),
            'highlights' => ['hardest_close' => null, 'fastest_turnaround' => null, 'most_collaborated' => null],
            'projects' => [],
            'standalone' => null,
        ];
    }
}
