<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Reply;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Carbon\Carbon;
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

    private array $ownedTaskIdsCache = [];

    public function report(User $subject, Carbon $start, Carbon $end): array
    {
        $key = sprintf(
            'tasksheet:%d:%s:%s:t%s:d%s:r%s',
            $subject->id,
            $start->toDateString(),
            $end->toDateString(),
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(TaskDetail::class),
            ModelCacheVersion::version(Reply::class)
        );

        return Cache::flexible($key, [self::FRESH_SECONDS, self::STALE_SECONDS], fn() => $this->buildReport($subject, $start, $end));
    }

    public function activityFeed(User $subject, Carbon $start, Carbon $end, int $page = 1, int $perPage = 30): array
    {
        $candidateTaskIds = $this->candidateTaskIds($subject, $start, $end);

        if ($candidateTaskIds->isEmpty()) {
            return ['days' => [], 'has_more' => false, 'next_page' => null];
        }

        $logger = app(ActivityLogger::class);

        $replies = Reply::where('repliable_type', Task::class)
            ->whereIn('repliable_id', $candidateTaskIds)
            ->whereIn('type', self::RELEVANT_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get(['id', 'repliable_id', 'user_id', 'type', 'payload', 'body', 'created_at']);

        $hasMore = $replies->count() > $perPage;
        $replies = $replies->take($perPage);

        $taskTitles = Task::whereKey($replies->pluck('repliable_id')->unique())->pluck('title', 'id');

        $items = $replies->map(fn(Reply $reply) => [
            'reply_id' => $reply->id,
            'task_id' => $reply->repliable_id,
            'task_title' => $taskTitles->get($reply->repliable_id),
            'actor_name' => $reply->user?->name ?? 'سیستم',
            ...$logger->render($reply),
            'created_at' => $reply->created_at->toIso8601String(),
        ]);

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

    private function buildReport(User $subject, Carbon $start, Carbon $end): array
    {
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        $ownedTaskIds = $this->ownedTaskIds($subject->id);
        $actedTaskIds = $this->actedTaskIds($subject->id, $start, $end);
        $candidateTaskIds = $ownedTaskIds->merge($actedTaskIds)->unique()->values();
        $ownedSet = $ownedTaskIds->flip();

        if ($candidateTaskIds->isEmpty()) {
            return $this->zeroStateReport($start, $end, $days);
        }

        $events = $this->loadEvents($candidateTaskIds, $start, $end);
        $tasks = $this->loadTasks($candidateTaskIds);

        $doneEvents = $this->latestDoneEvents($events);
        $completions = $this->completions($doneEvents, $tasks);
        $subjectCompletions = $completions->filter(fn(array $c) => $c['actor_id'] === $subject->id);
        $ownedCompletions = $subjectCompletions->filter(fn(array $c) => $ownedSet->has($c['task_id']));

        $ownedEvents = $events->filter(fn(Reply $e) => $ownedSet->has((int) $e->repliable_id));

        $metrics = $this->scorecardMetrics($ownedCompletions, $ownedEvents);
        $baselineMetrics = $this->baselineMetrics($subject, $start, $end);
        $hasBaseline = $baselineMetrics !== null && $baselineMetrics['completed'] >= 3;

        $touchedTasks = $this->touchedTasks($tasks, $ownedTaskIds, $actedTaskIds, $events);

        [$stillOverdue, $inProgress, $upcomingDeadline] = $this->taskStatusCounts($tasks->filter(fn(Task $t) => $ownedSet->has($t->id)));

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
                    'delta_percent' => $hasBaseline ? $this->deltaPercent($metrics['onTimePercent'], $baselineMetrics['onTimePercent']) : null,
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
            'weekly_totals' => $this->weeklyTotals($subjectCompletions, $start, $end),
            'narrative' => $this->narrative($metrics['completed'], $candidateTaskIds->count(), $metrics['onTimePercent'], $metrics['avg'], $metrics['approvalsReceived'], $days),
            'highlights' => $this->highlights($subjectCompletions, $events, $subject),
            'projects' => $this->projectGroups($touchedTasks, $subjectCompletions, $subject),
            'standalone' => $this->standaloneGroup($touchedTasks, $subjectCompletions),
        ];
    }

    private function baselineMetrics(User $subject, Carbon $start, Carbon $end): ?array
    {
        $length = $start->diffAsCarbonInterval($end);
        $baselineEnd = $start->copy();
        $baselineStart = $start->copy()->sub($length);

        $candidateTaskIds = $this->candidateTaskIds($subject, $baselineStart, $baselineEnd);

        if ($candidateTaskIds->isEmpty()) {
            return null;
        }

        $events = $this->loadEvents($candidateTaskIds, $baselineStart, $baselineEnd);
        $tasks = $this->loadTasks($candidateTaskIds);
        $ownedSet = $this->ownedTaskIds($subject->id)->flip();

        $completions = $this->completions($this->latestDoneEvents($events), $tasks)
            ->filter(fn(array $c) => $c['actor_id'] === $subject->id && $ownedSet->has($c['task_id']));

        $ownedEvents = $events->filter(fn(Reply $e) => $ownedSet->has((int) $e->repliable_id));

        return $this->scorecardMetrics($completions, $ownedEvents);
    }

    private function candidateTaskIds(User $subject, Carbon $start, Carbon $end): Collection
    {
        return $this->ownedTaskIds($subject->id)
            ->merge($this->actedTaskIds($subject->id, $start, $end))
            ->unique()
            ->values();
    }

    private function ownedTaskIds(int $userId): Collection
    {
        return $this->ownedTaskIdsCache[$userId] ??= Task::where(fn($q) => $q->where('assigned_to', $userId)
            ->orWhere('user_id', $userId)
            ->orWhereHas('detail', fn($d) => $d->whereJsonContains('collaborators', $userId)))
            ->pluck('id');
    }

    private function actedTaskIds(int $userId, Carbon $start, Carbon $end): Collection
    {
        return Reply::where('repliable_type', Task::class)
            ->where('user_id', $userId)
            ->whereIn('type', self::RELEVANT_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('repliable_id');
    }

    private function loadEvents(Collection $candidateTaskIds, Carbon $start, Carbon $end): Collection
    {
        return Reply::where('repliable_type', Task::class)
            ->whereIn('repliable_id', $candidateTaskIds)
            ->whereIn('type', self::RELEVANT_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'repliable_id', 'user_id', 'type', 'payload', 'created_at']);
    }

    private function loadTasks(Collection $candidateTaskIds): Collection
    {
        return Task::whereKey($candidateTaskIds)
            ->with([
                'project' => fn($q) => $q->withTrashed()->select(['id', 'name', 'owner_id', 'member_ids', 'deleted_at']),
                'detail:id,task_id,department_id,collaborators',
            ])
            ->get(['id', 'title', 'status', 'priority', 'deadline', 'project_id', 'created_at', 'completed_at'])
            ->keyBy('id');
    }

    private function latestDoneEvents(Collection $events): Collection
    {
        return $events
            ->filter(fn(Reply $e) => $e->type === TaskActivityType::StatusChange && ($e->payload['to'] ?? null) === TaskStatus::Done->value)
            ->groupBy('repliable_id')
            ->map(fn(Collection $group) => $group->sortBy([['created_at', 'asc'], ['id', 'asc']])->last());
    }

    private function completions(Collection $doneEvents, Collection $tasks): Collection
    {
        return $doneEvents
            ->map(function (Reply $event, int|string $taskId) use ($tasks) {
                $taskId = (int) $taskId;
                $task = $tasks->get($taskId);

                if (!$task) {
                    return null;
                }

                return [
                    'task_id' => $taskId,
                    'task' => $task,
                    'event' => $event,
                    'actor_id' => (int) ($event->user_id ?? 0),
                    'cycle_time_days' => (float) $task->created_at->diffInDays($event->created_at),
                    'on_time' => $task->deadline ? $event->created_at->lessThanOrEqualTo($task->deadline) : null,
                ];
            })
            ->filter()
            ->values()
            ->keyBy('task_id');
    }

    private function touchedTasks(Collection $tasks, Collection $ownedTaskIds, Collection $actedTaskIds, Collection $events): Collection
    {
        $ownedSet = $ownedTaskIds->flip();
        $actedSet = $actedTaskIds->flip();
        $activityTaskIds = $events->pluck('repliable_id')->unique()->flip();

        return $tasks->filter(fn(Task $task) => $actedSet->has($task->id) || ($ownedSet->has($task->id) && $activityTaskIds->has($task->id)));
    }

    private function taskStatusCounts(Collection $tasks): array
    {
        $now = now();
        $upcomingWindow = $now->copy()->addDays(7);

        return $tasks->reduce(function (array $acc, Task $t) use ($now, $upcomingWindow) {
            $notDone = $t->status !== TaskStatus::Done->value;

            if ($notDone && $t->deadline?->isPast()) {
                $acc[0]++;
            }
            if ($t->status === TaskStatus::InProgress->value) {
                $acc[1]++;
            }
            if ($notDone && $t->deadline && $t->deadline->between($now, $upcomingWindow)) {
                $acc[2]++;
            }

            return $acc;
        }, [0, 0, 0]);
    }

    private function scorecardMetrics(Collection $subjectCompletions, Collection $events): array
    {
        $completed = $subjectCompletions->count();
        $withDeadline = $subjectCompletions->filter(fn(array $c) => $c['on_time'] !== null);
        $onTimeCount = $withDeadline->filter(fn(array $c) => $c['on_time'] === true)->count();
        $sample = $withDeadline->count();

        $cycleDays = $subjectCompletions->pluck('cycle_time_days')->sort()->values();

        $approvalsReceived = $events->filter(fn(Reply $e) => $e->type === TaskActivityType::Approval && ($e->payload['approved'] ?? false) === true)->count();

        return [
            'completed' => $completed,
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

    private function weeklyTotals(Collection $subjectCompletions, Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $cursor = $start->copy()->startOfWeek(Carbon::SATURDAY);

        while ($cursor->lte($end)) {
            $weeks[$cursor->toDateString()] = 0;
            $cursor->addWeek();
        }

        foreach ($subjectCompletions as $completion) {
            $key = $completion['event']->created_at->copy()->startOfWeek(Carbon::SATURDAY)->toDateString();
            if (isset($weeks[$key])) {
                $weeks[$key]++;
            }
        }

        return array_values($weeks);
    }

    private function highlights(Collection $subjectCompletions, Collection $events, User $subject): array
    {
        if ($subjectCompletions->isEmpty()) {
            return ['hardest_close' => null, 'fastest_turnaround' => null, 'most_collaborated' => null];
        }

        $commentCounts = $events
            ->filter(fn(Reply $e) => $e->type === TaskActivityType::Comment && (int) $e->user_id !== $subject->id)
            ->groupBy('repliable_id')
            ->map->count();

        $hardest = $fastest = $mostCollaborated = null;
        $hardestScore = $fastestVal = $collabCount = null;

        foreach ($subjectCompletions as $completion) {
            $score = ($completion['task']->priority?->tier() ?? 0) * $completion['cycle_time_days'];
            if ($hardest === null || $score > $hardestScore || ($score === $hardestScore && $completion['task_id'] < $hardest['task_id'])) {
                $hardest = $completion;
                $hardestScore = $score;
            }

            $cycle = $completion['cycle_time_days'];
            if ($fastest === null || $cycle < $fastestVal || ($cycle === $fastestVal && $completion['task_id'] < $fastest['task_id'])) {
                $fastest = $completion;
                $fastestVal = $cycle;
            }

            $count = $commentCounts->get($completion['task_id'], 0);
            if ($mostCollaborated === null || $count > $collabCount || ($count === $collabCount && $completion['task_id'] < $mostCollaborated['task_id'])) {
                $mostCollaborated = $completion;
                $collabCount = $count;
            }
        }

        return [
            'hardest_close' => $this->highlightRow($hardest),
            'fastest_turnaround' => $this->highlightRow($fastest),
            'most_collaborated' => [
                ...$this->highlightRow($mostCollaborated),
                'comments_count' => $collabCount,
            ],
        ];
    }

    private function highlightRow(array $completion): array
    {
        return [
            'task_id' => $completion['task_id'],
            'title' => $completion['task']->title,
            'priority' => $completion['task']->priority?->value,
            'cycle_time_days' => $completion['cycle_time_days'],
        ];
    }

    private function projectGroups(Collection $touchedTasks, Collection $subjectCompletions, User $subject): array
    {
        return $touchedTasks->whereNotNull('project_id')
            ->groupBy('project_id')
            ->map(function (Collection $tasks) use ($subjectCompletions, $subject) {
                $project = $tasks->first()->project;
                $role = match (true) {
                    $project?->owner_id === $subject->id => 'owner',
                    in_array($subject->id, $project?->member_ids ?? [], true) => 'member',
                    default => 'collaborator',
                };

                return [
                    'project_id' => $project?->id,
                    'project_name' => $project?->name,
                    'is_archived' => (bool) $project?->trashed(),
                    'role' => $role,
                    ...$this->groupStats($tasks, $subjectCompletions),
                ];
            })
            ->values()
            ->all();
    }

    private function standaloneGroup(Collection $touchedTasks, Collection $subjectCompletions): ?array
    {
        $tasks = $touchedTasks->whereNull('project_id');

        return $tasks->isEmpty() ? null : $this->groupStats($tasks, $subjectCompletions);
    }

    private function groupStats(Collection $tasks, Collection $subjectCompletions): array
    {
        $completed = 0;
        $onTimeCount = 0;
        $withDeadlineCount = 0;
        $stillOverdue = 0;
        $inProgress = 0;
        $drilldown = [];

        foreach ($tasks as $task) {
            $completion = $subjectCompletions->get($task->id);
            $notDone = $task->status !== TaskStatus::Done->value;
            $isInProgress = $task->status === TaskStatus::InProgress->value;
            $isOverdue = $notDone && $task->deadline?->isPast();

            if ($completion) {
                $completed++;
                if ($completion['on_time'] !== null) {
                    $withDeadlineCount++;
                    if ($completion['on_time'] === true) {
                        $onTimeCount++;
                    }
                }
            }

            if ($isOverdue) {
                $stillOverdue++;
            }
            if ($isInProgress) {
                $inProgress++;
            }

            if ($completion || $isInProgress || $isOverdue) {
                $drilldown[] = $this->taskRow($task, $completion);
            }
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
            'title' => $task->title,
            'priority' => $task->priority?->value,
            'status' => $task->status,
            'deadline' => $task->deadline?->toIso8601String(),
            'completed_at' => $completion ? $completion['event']->created_at->toIso8601String() : $task->completed_at?->toIso8601String(),
            'cycle_time_days' => $completion['cycle_time_days'] ?? null,
            'on_time' => $completion['on_time'] ?? null,
        ];
    }

    private function narrative(int $completed, int $assigned, ?float $onTimePercent, ?float $avgCycle, int $approvalsReceived, int $days): string
    {
        $onTime = $onTimePercent !== null ? number_format($onTimePercent, 0) : '—';
        $avg = $avgCycle !== null ? number_format($avgCycle, 1) : '—';

        return "در بازهٔ {$days} روزه، {$completed} از {$assigned} وظیفهٔ محول‌شده را تکمیل کرد (٪{$onTime} به‌موقع)، میانگین زمان انجام {$avg} روز و {$approvalsReceived} تأیید مدیر دریافت کرد.";
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
