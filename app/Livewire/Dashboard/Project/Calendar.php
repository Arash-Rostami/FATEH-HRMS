<?php

namespace App\Livewire\Dashboard\Project;

use App\Enums\TaskActivityType;
use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Services\Cache\ModelCacheVersion;
use App\Values\CalendarRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Defer]
class Calendar extends Component
{
    #[Locked]
    public ?int $activeProjectId = null;
    #[Locked]
    public string $calendarNavDate = '';
    #[Locked]
    public string $selectedCalendarDay = '';

    private array $calendarBucketsCache = [];

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.calendar-placeholder');
    }

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;

        $today = Jalalian::now()->format('Y-m-d');
        $this->calendarNavDate = $today;
        $this->selectedCalendarDay = $today;
    }

    private function calendarBucketsCacheKey(int $projectId, string $navDate): string
    {
        return sprintf(
            'project-calendar:%d:%s:t%s:r%s',
            $projectId,
            $navDate,
            ModelCacheVersion::version(Task::class),
            ModelCacheVersion::version(Reply::class)
        );
    }

    private function calendarBuckets(string $navDate): array
    {
        $projectId = $this->activeProjectId;
        $key = ($projectId ?? 0) . ':' . $navDate;

        if (array_key_exists($key, $this->calendarBucketsCache)) {
            return $this->calendarBucketsCache[$key];
        }

        if (!$projectId) {
            return $this->calendarBucketsCache[$key] = $this->emptyCalendarBuckets();
        }

        try {
            return $this->calendarBucketsCache[$key] = Cache::remember(
                $this->calendarBucketsCacheKey($projectId, $navDate),
                now()->addSeconds(ModelCacheVersion::defaultSeconds()),
                fn() => $this->buildCalendarBuckets($projectId, $navDate)
            );
        } catch (\Throwable) {
            return $this->calendarBucketsCache[$key] = $this->emptyCalendarBuckets();
        }
    }

    private function emptyGantt(): array
    {
        return [
            'rows' => [],
            'daysCount' => 0,
            'dayNumbers' => [],
            'today' => Jalalian::now()->format('Y-m-d'),
            'todayIndex' => null,
            'projectDeadline' => null,
            'projectDeadlineIndex' => null,
            'hasAnyTask' => false,
        ];
    }

    private function emptyCalendarBuckets(): array
    {
        return [
            'deadlineByDay' => [],
            'lifecycleByDay' => [],
            'overdueCarry' => [],
            'gantt' => $this->emptyGantt(),
        ];
    }

    private function buildCalendarBuckets(int $projectId, string $navDate): array
    {
        $range = CalendarRange::fromNavigation($navDate, 'month');
        $deadlineByDay = [];
        $lifecycleByDay = [];
        $overdueCarry = [];
        $ganttRows = [];

        $today = Jalalian::now()->format('Y-m-d');
        $todayStart = Jalalian::fromFormat('Y-m-d', $today)->toCarbon()->startOfDay();
        $daysCount = count($range->days());
        $rangeStart = $range->start->copy()->startOfDay();
        $dayIndexOf = fn(Carbon $carbon): int => (int) round($rangeStart->diffInDays($carbon->copy()->startOfDay(), false));

        $tasks = Task::where('project_id', $projectId)
            ->with('detail:id,task_id,checklist')
            ->get(['id', 'title', 'created_at', 'deadline', 'status', 'archived_at', 'completed_at'])
            ->sort(function (Task $a, Task $b) {
                if (($a->deadline === null) xor ($b->deadline === null)) {
                    return $a->deadline === null ? 1 : -1;
                }
                return [$a->deadline?->timestamp, $a->created_at->timestamp] <=> [$b->deadline?->timestamp, $b->created_at->timestamp];
            });

        $slipCounts = [];
        $slipFrom = [];

        if ($tasks->isNotEmpty()) {
            $slips = Reply::query()
                ->where('repliable_type', Task::class)
                ->whereIn('repliable_id', Task::query()->where('project_id', $projectId)->select('id'))
                ->where('type', TaskActivityType::DeadlineChange)
                ->orderBy('created_at')
                ->get(['repliable_id', 'payload']);

            foreach ($slips as $reply) {
                $taskId = $reply->repliable_id;
                $slipCounts[$taskId] = ($slipCounts[$taskId] ?? 0) + 1;
                $slipFrom[$taskId] ??= $this->gregorianDayToJalali($reply->payload['from'] ?? null);
            }
        }

        foreach ($tasks as $task) {
            if ($task->deadline && $task->deadline->between($range->start, $range->end)) {
                $day = Jalalian::fromCarbon($task->deadline)->format('Y-m-d');
                $deadlineByDay[$day][] = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'date' => $day,
                    'time' => Jalalian::fromCarbon($task->deadline)->format('H:i'),
                    'isResolved' => $task->status === 'done' || $task->archived_at !== null,
                    'slipCount' => $slipCounts[$task->id] ?? 0,
                    'slipFrom' => $slipFrom[$task->id] ?? null,
                ];
            }

            if ($task->created_at->between($range->start, $range->end)) {
                $day = Jalalian::fromCarbon($task->created_at)->format('Y-m-d');
                $lifecycleByDay[$day][] = [
                    'marker' => 'start',
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'time' => Jalalian::fromCarbon($task->created_at)->format('H:i'),
                    'at' => $task->created_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        if ($tasks->isNotEmpty()) {
            $titles = $tasks->pluck('title', 'id');

            $statusChanges = Reply::query()
                ->where('repliable_type', Task::class)
                ->whereIn('repliable_id', Task::query()->where('project_id', $projectId)->select('id'))
                ->where('type', TaskActivityType::StatusChange)
                ->whereBetween('created_at', [$range->start, $range->end])
                ->orderBy('created_at')
                ->get(['id', 'repliable_id', 'payload', 'created_at']);

            foreach ($statusChanges as $reply) {
                $day = Jalalian::fromCarbon($reply->created_at)->format('Y-m-d');
                $to = $reply->payload['to'] ?? null;
                $lifecycleByDay[$day][] = [
                    'marker' => $to === 'done' ? 'completed' : 'change',
                    'task_id' => $reply->repliable_id,
                    'title' => $titles[$reply->repliable_id] ?? '',
                    'time' => Jalalian::fromCarbon($reply->created_at)->format('H:i'),
                    'from' => $reply->payload['from'] ?? null,
                    'to' => $to,
                    'at' => $reply->created_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        foreach ($tasks as $task) {
            $isOpen = $task->status !== 'done' && $task->archived_at === null;
            $hasDeadline = $task->deadline !== null;

            $visible = ($hasDeadline && $task->deadline->gte($rangeStart) && $task->created_at->lte($range->end))
                || (!$hasDeadline && $isOpen)
                || ($hasDeadline && $isOpen && $task->deadline->lt($rangeStart));

            if (!$visible) {
                continue;
            }

            if ($hasDeadline && $isOpen && $task->deadline->lt($rangeStart)) {
                $overdueCarry[] = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'date' => Jalalian::fromCarbon($task->deadline)->format('Y-m-d'),
                    'time' => Jalalian::fromCarbon($task->deadline)->format('H:i'),
                    'isResolved' => false,
                    'slipCount' => $slipCounts[$task->id] ?? 0,
                    'slipFrom' => $slipFrom[$task->id] ?? null,
                ];
            }

            $deadIdx = $hasDeadline ? $dayIndexOf($task->deadline) : null;
            $rawStartIdx = $dayIndexOf($task->created_at);
            $rawEndIdx = $task->completed_at !== null
                ? $dayIndexOf($task->completed_at)
                : ($hasDeadline ? $deadIdx : $daysCount - 1);

            $startIdx = max(0, min($rawStartIdx, $daysCount - 1));
            $endIdx = max(0, min($rawEndIdx, $daysCount - 1));

            if ($deadIdx !== null && $deadIdx < $rawStartIdx) {
                $startIdx = max(0, min($deadIdx, $daysCount - 1));
                $endIdx = $startIdx;
            }
            if ($endIdx < $startIdx) {
                $endIdx = $startIdx;
            }

            $tailStartPct = null;
            $tailWidthPct = null;

            if ($hasDeadline) {
                if ($isOpen && $task->deadline->copy()->startOfDay()->lt($todayStart)) {
                    $tailFrom = max($deadIdx + 1, 0);
                    $tailTo = min($dayIndexOf($todayStart), $daysCount - 1);
                    if ($tailTo >= $tailFrom) {
                        $tailStartPct = round($tailFrom / $daysCount * 100, 2);
                        $tailWidthPct = round(($tailTo - $tailFrom + 1) / $daysCount * 100, 2);
                    }
                } elseif (!$isOpen && $task->completed_at !== null
                    && $task->completed_at->copy()->startOfDay()->gt($task->deadline->copy()->startOfDay())) {
                    $tailFrom = max($deadIdx + 1, 0);
                    $tailTo = min($dayIndexOf($task->completed_at), $daysCount - 1);
                    if ($tailTo >= $tailFrom) {
                        $tailStartPct = round($tailFrom / $daysCount * 100, 2);
                        $tailWidthPct = round(($tailTo - $tailFrom + 1) / $daysCount * 100, 2);
                    }
                }
            }

            $ganttRows[] = [
                'task_id' => $task->id,
                'title' => $task->title,
                'startPct' => round($startIdx / $daysCount * 100, 2),
                'widthPct' => round((($endIdx - $startIdx) + 1) / $daysCount * 100, 2),
                'progressPct' => $task->progress_percent,
                'slipCount' => $slipCounts[$task->id] ?? 0,
                'slipFrom' => $slipFrom[$task->id] ?? null,
                'isDone' => $task->status === 'done' || $task->archived_at !== null,
                'hasDeadline' => $hasDeadline,
                'leftClipped' => $rawStartIdx < 0,
                'rightClipped' => $rawEndIdx > $daysCount - 1 || (!$hasDeadline && $isOpen),
                'tailStartPct' => $tailStartPct,
                'tailWidthPct' => $tailWidthPct,
            ];
        }

        $todayIdx = $dayIndexOf($todayStart);
        $dayNumbers = array_map(fn(Carbon $day) => (int) Jalalian::fromCarbon($day)->format('j'), $range->days());

        $projectDeadline = null;
        $projectDeadlineIndex = null;

        try {
            $deadlineSetting = Project::query()->find($projectId)?->setting('deadline');
            if ($deadlineSetting) {
                $deadlineCarbon = Carbon::parse($deadlineSetting)->startOfDay();
                $projectDeadline = Jalalian::fromCarbon($deadlineCarbon)->format('Y-m-d');
                $deadlineIdx = $dayIndexOf($deadlineCarbon);
                if ($deadlineIdx >= 0 && $deadlineIdx < $daysCount) {
                    $projectDeadlineIndex = $deadlineIdx;
                }
            }
        } catch (\Throwable) {
            $projectDeadline = null;
            $projectDeadlineIndex = null;
        }

        return [
            'deadlineByDay' => $deadlineByDay,
            'lifecycleByDay' => $lifecycleByDay,
            'overdueCarry' => $overdueCarry,
            'gantt' => [
                'rows' => $ganttRows,
                'daysCount' => $daysCount,
                'dayNumbers' => $dayNumbers,
                'today' => $today,
                'todayIndex' => $todayIdx >= 0 && $todayIdx < $daysCount ? $todayIdx : null,
                'projectDeadline' => $projectDeadline,
                'projectDeadlineIndex' => $projectDeadlineIndex,
                'hasAnyTask' => $tasks->isNotEmpty(),
            ],
        ];
    }

    private function gregorianDayToJalali(?string $gregorian): ?string
    {
        if (!$gregorian) {
            return null;
        }

        try {
            return Jalalian::fromCarbon(Carbon::parse($gregorian))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function deadlineEventsByDay(string $navDate): array
    {
        return $this->calendarBuckets($navDate)['deadlineByDay'];
    }

    private function lifecycleEventsByDay(string $navDate): array
    {
        return $this->calendarBuckets($navDate)['lifecycleByDay'];
    }

    #[Computed]
    public function ganttRows(): array
    {
        if (!$this->activeProjectId || $this->calendarNavDate === '') {
            return $this->emptyGantt();
        }

        try {
            return $this->calendarBuckets($this->calendarNavDate)['gantt'] ?? $this->emptyGantt();
        } catch (\Throwable) {
            return $this->emptyGantt();
        }
    }

    #[Computed]
    public function overdueCarry(): array
    {
        if (!$this->activeProjectId || $this->calendarNavDate === '') {
            return [];
        }

        try {
            return $this->calendarBuckets($this->calendarNavDate)['overdueCarry'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    #[Computed]
    public function calendarDays(): array
    {
        $empty = ['days' => [], 'label' => '', 'weekLabels' => CalendarRange::WEEK_DAY_LABELS];
        if (!$this->activeProjectId || $this->calendarNavDate === '') {
            return $empty;
        }

        try {
            $range = CalendarRange::fromNavigation($this->calendarNavDate, 'month');
        } catch (\Throwable) {
            return $empty;
        }

        $byDay = $this->deadlineEventsByDay($this->calendarNavDate);
        $lifecycleByDay = $this->lifecycleEventsByDay($this->calendarNavDate);
        $projectDeadline = $this->calendarBuckets($this->calendarNavDate)['gantt']['projectDeadline'] ?? null;
        $today = Jalalian::now()->format('Y-m-d');
        $offset = $range->weekdayOffset();
        $days = [];
        for ($i = 0; $i < $offset; $i++) {
            $days[] = null;
        }
        foreach ($range->days() as $carbon) {
            $date = Jalalian::fromCarbon($carbon)->format('Y-m-d');
            $dayNum = (int) Jalalian::fromCarbon($carbon)->format('j');
            $buckets = $byDay[$date] ?? [];
            $markerTypes = array_column($lifecycleByDay[$date] ?? [], 'marker');
            $days[] = [
                'day' => $dayNum,
                'date' => $date,
                'isToday' => $date === $today,
                'isSelected' => $date === $this->selectedCalendarDay,
                'hasDeadline' => !empty($buckets),
                'hasOpenDeadline' => in_array(false, array_column($buckets, 'isResolved'), true),
                'deadlineCount' => count($buckets),
                'hasStart' => in_array('start', $markerTypes, true),
                'hasChange' => in_array('change', $markerTypes, true),
                'hasCompleted' => in_array('completed', $markerTypes, true),
                'hasProjectDeadline' => $projectDeadline !== null && $date === $projectDeadline,
            ];
        }

        return [
            'days' => $days,
            'label' => $range->jalaliLabel(),
            'weekLabels' => CalendarRange::WEEK_DAY_LABELS,
            'today' => $today,
        ];
    }

    #[Computed]
    public function monthAgenda(): array
    {
        if (!$this->activeProjectId || $this->calendarNavDate === '') {
            return [];
        }

        $deadlines = collect($this->deadlineEventsByDay($this->calendarNavDate))
            ->flatMap(fn(array $events, string $date) => collect($events)->map(fn(array $e) => [
                'marker' => 'deadline',
                'date' => $date,
                'day' => (int) Jalalian::fromFormat('Y-m-d', $date)->format('j'),
                'title' => $e['title'],
                'task_id' => $e['task_id'],
                'time' => $e['time'],
                'isResolved' => $e['isResolved'],
                'slipCount' => $e['slipCount'] ?? 0,
                'slipFrom' => $e['slipFrom'] ?? null,
            ]));

        $lifecycle = collect($this->lifecycleEventsByDay($this->calendarNavDate))
            ->flatMap(fn(array $events, string $date) => collect($events)->map(fn(array $e) => [
                'marker' => $e['marker'],
                'date' => $date,
                'day' => (int) Jalalian::fromFormat('Y-m-d', $date)->format('j'),
                'title' => $e['title'],
                'task_id' => $e['task_id'],
                'time' => $e['time'],
                'from' => $e['from'] ?? null,
                'to' => $e['to'] ?? null,
            ]));

        return $deadlines->concat($lifecycle)
            ->sortBy([['date', 'asc'], ['time', 'asc']])
            ->values()
            ->all();
    }

    #[Computed]
    public function selectedDayDeadlines(): array
    {
        if (!$this->activeProjectId || $this->selectedCalendarDay === '') {
            return [];
        }

        return $this->deadlineEventsByDay($this->calendarNavDate)[$this->selectedCalendarDay] ?? [];
    }

    #[Computed]
    public function selectedDayLifecycle(): array
    {
        if (!$this->activeProjectId || $this->selectedCalendarDay === '') {
            return [];
        }

        $events = $this->lifecycleEventsByDay($this->calendarNavDate)[$this->selectedCalendarDay] ?? [];

        return collect($events)->map(function (array $event) {
            if (($event['from'] ?? null) !== 'pending') {
                return $event;
            }

            $prior = Reply::query()
                ->where('repliable_type', Task::class)
                ->where('repliable_id', $event['task_id'])
                ->where('type', TaskActivityType::StatusChange)
                ->where('created_at', '<', $event['at'])
                ->orderByDesc('created_at')
                ->first(['created_at']);

            if ($prior) {
                $event['pausedMinutes'] = $prior->created_at->diffInMinutes($event['at']);
            }

            return $event;
        })->sortBy('at')->values()->all();
    }

    #[Computed]
    public function selectedDayTimeline(): array
    {
        $deadlines = collect($this->selectedDayDeadlines)->map(fn(array $e) => [...$e, 'marker' => 'deadline']);

        return $deadlines->concat($this->selectedDayLifecycle)
            ->sortBy('time')
            ->values()
            ->all();
    }

    public function prevMonth(): void
    {
        $this->calendarNavDate = CalendarRange::advanceMonths($this->calendarNavDate, -1);
        $this->invalidateProjectCalendarComputeds();
    }

    public function nextMonth(): void
    {
        $this->calendarNavDate = CalendarRange::advanceMonths($this->calendarNavDate, 1);
        $this->invalidateProjectCalendarComputeds();
    }

    public function calendarToday(): void
    {
        $today = Jalalian::now()->format('Y-m-d');
        $this->calendarNavDate = $today;
        $this->selectedCalendarDay = $today;
        $this->invalidateProjectCalendarComputeds();
    }

    public function selectCalendarDay(string $jalaliYmd): void
    {
        try {
            Jalalian::fromFormat('Y-m-d', $jalaliYmd);
        } catch (\Throwable) {
            return;
        }

        $this->selectedCalendarDay = $jalaliYmd;
        $this->invalidateProjectCalendarComputeds();
    }

    private function invalidateProjectCalendarComputeds(): void
    {
        $this->calendarBucketsCache = [];
        unset($this->calendarDays, $this->selectedDayDeadlines, $this->selectedDayLifecycle, $this->selectedDayTimeline, $this->monthAgenda, $this->ganttRows, $this->overdueCarry);
    }

    public function refreshCalendar(): void
    {
        $this->calendarBucketsCache = [];
        unset($this->calendarDays, $this->selectedDayDeadlines, $this->selectedDayLifecycle, $this->selectedDayTimeline, $this->monthAgenda, $this->ganttRows, $this->overdueCarry);
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.calendar', [
            'presenter' => new ProjectPresenter(),
        ]);
    }
}
