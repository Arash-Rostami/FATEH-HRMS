<?php

namespace App\Services\Ticket;

use App\Filament\Resources\ThsResource\Enums\TicketStatus;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Department;
use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketInsightsService
{
    private const WINDOW_DAYS = 90;
    private const STALE_DAYS = 7;
    private const MIN_SAMPLE = 3;
    private const TTL_MINUTES = 10;
    private const TYPICAL_MIN = 5;

    public function forDepartment(?string $dept): ?array
    {
        if (!$dept) {
            return null;
        }

        return ModelCacheVersion::remember(
            Ticket::class,
            "insights:dept:{$dept}:r" . ModelCacheVersion::version(Reply::class),
            now()->addMinutes(self::TTL_MINUTES),
            fn() => $this->summarize($dept),
        );
    }

    public function typicalCloseMap(?string $dept): array
    {
        $dept ??= Ticket::defaultTargetDepartment();

        if (!$dept) {
            return [];
        }

        return ModelCacheVersion::remember(
            Ticket::class,
            "insights:typical:{$dept}",
            now()->addMinutes(self::TTL_MINUTES),
            function () use ($dept) {
                $buckets = [];

                $this->resolvedScope($dept)
                    ->where('completion_date', '>=', now()->subDays(self::WINDOW_DAYS))
                    ->selectRaw('request_type, request_area, TIMESTAMPDIFF(SECOND, created_at, completion_date) AS secs')
                    ->toBase()
                    ->get()
                    ->each(function ($r) use (&$buckets) {
                        if ($r->secs === null || $r->secs < 0) {
                            return;
                        }

                        foreach (["ta:{$r->request_type}|{$r->request_area}", "t:{$r->request_type}", 'd'] as $key) {
                            $buckets[$key][] = (int)$r->secs;
                        }
                    });

                return collect($buckets)
                    ->filter(fn(array $v) => count($v) >= self::TYPICAL_MIN)
                    ->map(fn(array $v) => max(1, (int)round($this->median($v) / 86400)))
                    ->all();
            },
        );
    }

    public function typicalCloseDays(array $map, ?string $type, ?string $area): ?int
    {
        return $map["ta:{$type}|{$area}"] ?? $map["t:{$type}"] ?? $map['d'] ?? null;
    }

    public function ticketTimeline(Ticket $ticket): array
    {
        $ticket->loadMissing('assignee');
        $strings = fn(string $key, array $replace = []) => __("resources/ths/strings.insight.timeline.{$key}", $replace);

        $firstAt = Reply::query()
            ->where('repliable_type', $ticket->getMorphClass())
            ->where('repliable_id', $ticket->id)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $ticket->requester_id)
            ->min('created_at');

        $days = $ticket->status === 'closed'
            ? null
            : $this->typicalCloseDays($this->typicalCloseMap($ticket->effectiveTargetDepartment()), $ticket->request_type, $ticket->request_area);

        return [
            'opened' => $strings('opened', ['when' => $ticket->created_at ? ($ticket->created_at->diffInDays(Carbon::now()) >= 30 ? toJalali($ticket->created_at, 'j F Y') : toJalaliRelative($ticket->created_at)) : '—']),
            'assignee' => $ticket->assignee ? $strings('assignee', ['name' => $ticket->assignee->name]) : null,
            'response' => match (true) {
                $firstAt !== null && $ticket->created_at !== null => $strings('replied', ['time' => $this->span(max(0, (int)$ticket->created_at->diffInSeconds(Carbon::parse($firstAt))))]),
                $ticket->status === 'closed' => $strings('no_response'),
                default => $strings('awaiting'),
            },
            'typical' => $days ? $strings('typical', ['days' => $this->fa($days)]) : null,
        ];
    }

    public function forTicket(Ticket $ticket): array
    {
        $ticket->loadMissing('assignee');
        $dept = $ticket->effectiveTargetDepartment();

        $replies = Reply::query()
            ->where('repliable_type', $ticket->getMorphClass())
            ->where('repliable_id', $ticket->id)
            ->orderBy('created_at')->orderBy('id')
            ->get(['user_id', 'created_at']);

        $similar = $this->scope($dept)
            ->where('request_type', $ticket->request_type)
            ->where('request_area', $ticket->request_area)
            ->whereKeyNot($ticket->id)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'request_subject', 'status', 'extra', 'created_at']);

        $otherOpen = Ticket::query()
            ->where('requester_id', $ticket->requester_id)
            ->where('status', '!=', 'closed')
            ->whereKeyNot($ticket->id)
            ->count();

        return [
            'cards' => [
                $this->ageCard($ticket),
                $this->durationCard($ticket, $dept),
                $this->deadlineCard($ticket),
                $this->firstResponseCard($ticket, $replies),
                $this->ownerCard($ticket, $replies->count()),
                $this->card('requester-open', 'heroicon-o-user-circle', 'requester_open', $this->fa($otherOpen), $otherOpen ? 'warning' : 'gray'),
            ],
            'similar' => $similar->map(fn(Ticket $t) => [
                'id' => $t->id,
                'code' => (new TicketPresenter())->formatId($t->toArray()),
                'subject' => $t->request_subject,
                'status' => TicketStatus::tryFrom((string)$t->status)?->getLabel() ?? (string)$t->status,
                'color' => TicketStatus::tryFrom((string)$t->status)?->getColor() ?? 'gray',
            ])->all(),
        ];
    }

    private function ageCard(Ticket $ticket): array
    {
        $status = TicketStatus::tryFrom((string)$ticket->status);

        return $this->card(
            'age',
            'heroicon-o-clock',
            'age',
            $ticket->created_at ? toJalaliRelative($ticket->created_at, false, 0) : '—',
            $status?->getColor() ?? 'gray',
            ($status?->getLabel() ?? (string)$ticket->status) . ($ticket->created_at ? ' · ' . toJalali($ticket->created_at, 'Y/m/d') : ''),
        );
    }

    private function baseline(Ticket $ticket, ?string $dept): ?array
    {
        $pool = array_filter($this->closedDurations($dept), fn(array $r) => $r[0] !== $ticket->id);

        $levels = [
            'type_area' => fn(array $r) => $r[1] === $ticket->request_type && $r[2] === $ticket->request_area,
            'type' => fn(array $r) => $r[1] === $ticket->request_type,
            'dept' => fn(array $r) => true,
        ];

        foreach ($levels as $basis => $match) {
            $secs = array_column(array_filter($pool, $match), 3);

            if (count($secs) >= self::MIN_SAMPLE) {
                return [$this->median($secs), $basis, count($secs)];
            }
        }

        return null;
    }

    private function card(string $key, string $icon, string $label, string $value, string $color = 'gray', string $hint = ''): array
    {
        return [
            'key' => $key,
            'icon' => $icon,
            'label' => __("resources/ths/strings.insight.cards.{$label}"),
            'value' => $value,
            'color' => $color,
            'hint' => $hint,
        ];
    }

    private function closedDurations(?string $dept): array
    {
        return ModelCacheVersion::remember(
            Ticket::class,
            'insights:closed:' . ($dept ?? '*'),
            now()->addMinutes(self::TTL_MINUTES),
            fn() => $this->resolvedScope($dept)
                ->selectRaw('id, request_type, request_area, TIMESTAMPDIFF(SECOND, created_at, completion_date) AS secs')
                ->toBase()
                ->get()
                ->filter(fn($r) => $r->secs !== null && $r->secs >= 0)
                ->map(fn($r) => [(int)$r->id, $r->request_type, $r->request_area, (int)$r->secs])
                ->values()
                ->all(),
        );
    }

    private function deadlineCard(Ticket $ticket): array
    {
        $deadline = $ticket->completion_deadline;

        if (!$deadline) {
            return $this->card('deadline', 'heroicon-o-calendar', 'deadline', __('resources/ths/strings.insight.no_deadline'));
        }

        $reference = $ticket->completion_date ?? ($ticket->status === 'closed' ? null : now());
        $late = $reference ? (int)$deadline->diffInSeconds($reference, false) : 0;
        $hint = toJalali($deadline, 'Y/m/d H:i');

        return $late > 0
            ? $this->card('deadline', 'heroicon-o-calendar', 'deadline', __('resources/ths/strings.insight.late', ['time' => $this->span($late)]), 'danger', $hint)
            : $this->card('deadline', 'heroicon-o-calendar', 'deadline', __('resources/ths/strings.insight.on_time'), 'success', $hint);
    }

    private function durationCard(Ticket $ticket, ?string $dept): array
    {
        $closed = $ticket->status === 'closed';
        $label = $closed ? 'duration_closed' : 'duration_running';
        $end = $closed ? $ticket->completion_date : now();

        if (!$ticket->created_at || !$end) {
            return $this->card('duration', 'heroicon-o-clock', $label, '—');
        }

        $secs = max(0, (int)$ticket->created_at->diffInSeconds($end));
        $baseline = $this->baseline($ticket, $dept);

        if (!$baseline || !$baseline[0]) {
            return $this->card('duration', 'heroicon-o-clock', $label, $this->span($secs), 'gray', __('resources/ths/strings.insight.no_baseline'));
        }

        [$median, $basis, $count] = $baseline;
        $ratio = $secs / $median;

        return $this->card(
            'duration',
            'heroicon-o-clock',
            $label,
            __('resources/ths/strings.insight.ratio', ['ratio' => $this->decimal($ratio)]),
            match (true) {
                $ratio <= 1.25 => 'success',
                $ratio <= 2 => 'warning',
                default => 'danger',
            },
            $this->span($secs) . ' · ' . __('resources/ths/strings.insight.baseline', [
                'median' => $this->span($median),
                'basis' => __("resources/ths/strings.insight.basis.{$basis}"),
                'count' => $this->fa($count),
            ]),
        );
    }

    private function decimal(float $value): string
    {
        return str_replace('.', '٫', $this->fa(number_format($value, 1, '.', '')));
    }

    private function fa(mixed $value): string
    {
        return convertToPersian((string)$value) ?? '';
    }

    private function firstResponseCard(Ticket $ticket, Collection $replies): array
    {
        $first = $replies->first(fn(Reply $r) => $r->user_id && (int)$r->user_id !== (int)$ticket->requester_id);

        if (!$first || !$ticket->created_at) {
            return $this->card('first-response', 'heroicon-o-chat-bubble-left-right', 'first_response', __('resources/ths/strings.insight.no_reply'), $ticket->status === 'closed' ? 'gray' : 'warning');
        }

        return $this->card('first-response', 'heroicon-o-chat-bubble-left-right', 'first_response', $this->span(max(0, (int)$ticket->created_at->diffInSeconds($first->created_at))), 'info');
    }

    private function groupLabel(object $row, string $dept): string
    {
        $type = Ticket::getCustomRequestTypeLabel($row->request_type, $dept);
        $area = Ticket::getCustomRequestAreaLabel($row->request_type, $row->request_area, $dept);

        return $area !== '' ? "{$type} / {$area}" : ($type !== '' ? $type : '—');
    }

    private function median(array $values): ?float
    {
        if (!$values) {
            return null;
        }

        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);

        return $count % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    private function ownerCard(Ticket $ticket, int $replies): array
    {
        $files = count((array)$ticket->requester_files) + count((array)$ticket->assignee_files);

        [$value, $color] = match (true) {
            blank($ticket->assigned_to) => [__('resources/ths/strings.fields.unassigned'), 'gray'],
            $ticket->assignee === null => [__('resources/ths/strings.insight.deleted_user'), 'danger'],
            default => [$ticket->assignee->name, 'info'],
        };

        return $this->card('owner', 'heroicon-o-user-circle', 'owner', $value, $color, __('resources/ths/strings.insight.owner_hint', [
            'replies' => $this->fa($replies),
            'files' => $this->fa($files),
        ]));
    }

    private function percentile(array $values, float $p): ?float
    {
        if (!$values) {
            return null;
        }

        sort($values);

        return $values[max(0, (int)ceil($p * count($values)) - 1)];
    }

    private function resolvedScope(?string $dept): Builder
    {
        return $this->scope($dept)
            ->where('status', 'closed')
            ->whereNotNull('completion_date')
            ->where(fn(Builder $q) => $q->whereNull('action_result')->orWhere('action_result', '!=', Ticket::WITHDRAWN_RESULT));
    }

    private function scope(?string $dept): Builder
    {
        return Ticket::query()->when($dept, fn(Builder $q) => $q->targetedAt($dept));
    }

    private function span(?float $secs): string
    {
        if ($secs === null) {
            return '—';
        }

        $secs = max(0, (int)round($secs));
        [$d, $h, $m] = [intdiv($secs, 86400), intdiv($secs % 86400, 3600), intdiv($secs % 3600, 60)];
        $unit = fn(string $k) => __("resources/ths/strings.insight.units.{$k}");
        $and = ' ' . $unit('and') . ' ';

        return match (true) {
            $d > 0 => $this->fa($d) . ' ' . $unit('day') . ($h ? $and . $this->fa($h) . ' ' . $unit('hour') : ''),
            $h > 0 => $this->fa($h) . ' ' . $unit('hour') . ($m ? $and . $this->fa($m) . ' ' . $unit('minute') : ''),
            $m > 0 => $this->fa($m) . ' ' . $unit('minute'),
            default => $unit('under_minute'),
        };
    }

    private function summarize(string $dept): array
    {
        $now = now();
        $limit = self::WINDOW_DAYS * 86400;

        $rows = $this->scope($dept)
            ->where(fn(Builder $q) => $q->where('created_at', '>=', $now->copy()->subDays(self::WINDOW_DAYS))->orWhere('status', '!=', 'closed'))
            ->selectRaw('id, status, assigned_to, request_type, request_area, effectiveness, satisfaction_score, action_result, TIMESTAMPDIFF(SECOND, created_at, completion_date) AS close_secs, TIMESTAMPDIFF(SECOND, created_at, ?) AS age_secs', [$now->toDateTimeString()])
            ->toBase()
            ->get();

        $window = $rows->filter(fn($r) => $r->age_secs !== null && $r->age_secs <= $limit);
        $active = $rows->where('status', '!=', 'closed');
        $resolved = $window->filter(fn($r) => $r->status === 'closed' && $r->close_secs !== null && $r->close_secs >= 0 && $r->action_result !== Ticket::WITHDRAWN_RESULT);

        $status = $window->countBy(fn($r) => $r->status);
        $closeSecs = $resolved->pluck('close_secs')->map(fn($v) => (int)$v)->all();
        $responseSecs = $this->firstResponses($window);
        $stale = $active->filter(fn($r) => (int)$r->age_secs > self::STALE_DAYS * 86400)->count();
        $unassigned = $active->filter(fn($r) => $r->status === 'open' && blank($r->assigned_to))->count();

        $satisfaction = $window->pluck('satisfaction_score')->filter(fn($v) => $v !== null)->map(fn($v) => (float)$v);
        $effectiveness = $window->pluck('effectiveness')->filter(fn($v) => is_numeric($v) && $v >= 1 && $v <= 5)->map(fn($v) => (float)$v);

        $t = fn(string $key) => __("resources/ths/strings.insight.dept.{$key}");
        $c = fn(string $key, string $icon, string $value, string $color = 'gray', string $hint = '') => $this->card($key, $icon, $key, $value, $color, $hint);
        $rating = fn(?float $v) => match (true) {
            $v === null => 'gray',
            $v >= 4 => 'success',
            $v >= 2 => 'warning',
            default => 'danger',
        };
        $score = fn(Collection $v) => $v->isEmpty() ? '—' : $this->decimal($v->avg()) . ' / ' . $this->fa(5);

        $key = fn($r) => $r->request_type . '|' . $r->request_area;
        $averages = $resolved->groupBy($key)->map(fn(Collection $g) => ['n' => $g->count(), 'avg' => $g->avg('close_secs')]);
        $groups = $window->groupBy($key)->map(fn(Collection $g, string $k) => [
            'label' => $this->groupLabel($g->first(), $dept),
            'count' => $g->count(),
            'n' => $averages[$k]['n'] ?? 0,
            'avg' => $averages[$k]['avg'] ?? null,
        ]);

        $workload = $rows->whereNotNull('assigned_to')->groupBy('assigned_to')
            ->map(fn(Collection $g) => ['open' => $g->where('status', '!=', 'closed')->count(), 'closed' => $g->where('status', 'closed')->count()])
            ->sortByDesc(fn(array $w) => [$w['open'] + $w['closed'], $w['open']])
            ->take(5);
        $names = User::query()->whereKey($workload->keys()->all())->pluck('name', 'id');

        return [
            'department' => Department::getCachedModels()->get($dept)?->displayLabel() ?? $dept,
            'empty' => $window->isEmpty(),
            'sections' => [
                [
                    'key' => 'volume',
                    'label' => $t('volume'),
                    'cards' => [
                        $c('open', 'heroicon-o-envelope-open', $this->fa($status['open'] ?? 0), 'info'),
                        $c('in_progress', 'heroicon-o-arrow-path', $this->fa($status['in-progress'] ?? 0), 'warning'),
                        $c('closed', 'heroicon-o-check-circle', $this->fa($status['closed'] ?? 0), 'success'),
                        $c('per_week', 'heroicon-o-calendar-days', $this->decimal($window->count() / (self::WINDOW_DAYS / 7)), 'gray', $t('total') . ': ' . $this->fa($window->count())),
                    ],
                ],
                [
                    'key' => 'speed',
                    'label' => $t('speed'),
                    'cards' => [
                        $c('median_close', 'heroicon-o-clock', $this->span($this->median($closeSecs)), 'gray', $t('sample') . ': ' . $this->fa(count($closeSecs))),
                        $c('p90_close', 'heroicon-o-bolt', $this->span($this->percentile($closeSecs, 0.9))),
                        $c('median_first', 'heroicon-o-chat-bubble-left-right', $this->span($this->median($responseSecs)), 'info', $t('sample') . ': ' . $this->fa(count($responseSecs))),
                    ],
                ],
                [
                    'key' => 'backlog',
                    'label' => $t('backlog'),
                    'cards' => [
                        $c('stale', 'heroicon-o-exclamation-triangle', $this->fa($stale), $stale ? 'danger' : 'success', $t('stale_hint')),
                        $c('unassigned', 'heroicon-o-user-minus', $this->fa($unassigned), $unassigned ? 'warning' : 'success'),
                    ],
                ],
                [
                    'key' => 'quality',
                    'label' => $t('quality'),
                    'cards' => [
                        $c('avg_satisfaction', 'heroicon-o-star', $score($satisfaction), $rating($satisfaction->avg()), $t('sample') . ': ' . $this->fa($satisfaction->count())),
                        $c('avg_effectiveness', 'heroicon-o-star', $score($effectiveness), $rating($effectiveness->avg()), $t('sample') . ': ' . $this->fa($effectiveness->count())),
                    ],
                ],
            ],
            'tables' => [
                [
                    'key' => 'top-count',
                    'label' => $t('top_count'),
                    'columns' => [$t('col_area'), $t('col_count'), $t('col_avg')],
                    'rows' => $groups->sortByDesc('count')->take(5)
                        ->map(fn(array $g) => [$g['label'], $this->fa($g['count']), $g['avg'] === null ? '—' : $this->span($g['avg'])])
                        ->values()->all(),
                ],
                [
                    'key' => 'top-slow',
                    'label' => $t('top_slow'),
                    'columns' => [$t('col_area'), $t('col_avg'), $t('col_closed')],
                    'rows' => $groups->filter(fn(array $g) => $g['n'] >= 2)->sortByDesc('avg')->take(5)
                        ->map(fn(array $g) => [$g['label'], $this->span($g['avg']), $this->fa($g['n'])])
                        ->values()->all(),
                ],
                [
                    'key' => 'workload',
                    'label' => $t('workload'),
                    'columns' => [$t('col_assignee'), $t('col_open'), $t('col_closed')],
                    'rows' => $workload
                        ->map(fn(array $w, $id) => [$names[$id] ?? __('resources/ths/strings.insight.deleted_user'), $this->fa($w['open']), $this->fa($w['closed'])])
                        ->values()->all(),
                ],
            ],
        ];
    }

    private function firstResponses(Collection $window): array
    {
        if ($window->isEmpty()) {
            return [];
        }

        return DB::table('replies')
            ->join('tickets', 'tickets.id', '=', 'replies.repliable_id')
            ->where('replies.repliable_type', Ticket::class)
            ->whereIn('replies.repliable_id', $window->pluck('id')->all())
            ->whereNotNull('replies.user_id')
            ->whereColumn('replies.user_id', '!=', 'tickets.requester_id')
            ->groupBy('replies.repliable_id', 'tickets.created_at')
            ->selectRaw('TIMESTAMPDIFF(SECOND, tickets.created_at, MIN(replies.created_at)) AS secs')
            ->pluck('secs')
            ->filter(fn($v) => $v !== null)
            ->map(fn($v) => max(0, (int)$v))
            ->all();
    }
}
