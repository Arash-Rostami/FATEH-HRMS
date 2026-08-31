<?php

namespace App\Livewire\Dashboard\Tasksheet\Presentation;

use App\Traits\RiskEscalationChip;
use Carbon\Carbon;

class TasksheetPresenter
{
    use RiskEscalationChip;

    private const DELTA_EPSILON = 0.05;

    private const NEUTRAL_CHIP_CLASSES = 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline)_25%,transparent)]';

    private const PRIORITY_LABELS = ['urgent' => 'فوری', 'high' => 'بالا', 'medium' => 'متوسط', 'low' => 'کم'];

    private const PRIORITY_CHIP_CLASSES = [
        'urgent' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
        'high' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'medium' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'low' => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]',
    ];

    public function windowBounds(array $window): array
    {
        return [Carbon::parse($window['start']), Carbon::parse($window['end'])];
    }

    public function scorecardBlocks(array $report): array
    {
        $sc = $report['scorecard'];

        $tiles = [
            ['icon' => 'task_alt', 'label' => 'تکمیل‌شده', 'value' => convertToPersian($sc['completed']['value']), 'chip' => $this->deltaChip($sc['completed']['value'], $sc['completed']['previous']), 'ring' => null, 'spark' => $this->spark($sc['completed']['previous'], $sc['completed']['value'])],
            ['icon' => 'speed', 'label' => 'به‌موقع بودن', 'value' => $sc['on_time_percent']['value'] !== null ? convertToPersian($sc['on_time_percent']['value']) . '٪' : '—', 'chip' => $this->deltaChip($sc['on_time_percent']['value'], $sc['on_time_percent']['previous']), 'ring' => $sc['on_time_percent']['value'], 'spark' => $this->spark($sc['on_time_percent']['previous'], $sc['on_time_percent']['value'])],
            ['icon' => 'schedule', 'label' => 'میانگین زمان انجام', 'value' => $sc['cycle_time_days']['median'] !== null ? convertToPersian(number_format($sc['cycle_time_days']['median'], 1)) . ' روز' : '—', 'chip' => $this->deltaChip($sc['cycle_time_days']['median'], $sc['cycle_time_days']['previous_median']), 'ring' => null, 'spark' => $this->spark($sc['cycle_time_days']['previous_median'], $sc['cycle_time_days']['median'])],
            ['icon' => 'verified', 'label' => 'تأییدیهٔ دریافتی', 'value' => convertToPersian($sc['approvals_received']['value']), 'chip' => $this->deltaChip($sc['approvals_received']['value'], $sc['approvals_received']['previous']), 'ring' => null, 'spark' => $this->spark($sc['approvals_received']['previous'], $sc['approvals_received']['value'])],
        ];

        $plainTiles = [
            ['icon' => 'warning', 'label' => 'همچنان معوق', 'value' => convertToPersian($sc['still_overdue'])],
            ['icon' => 'autorenew', 'label' => 'در حال انجام', 'value' => convertToPersian($sc['in_progress'])],
        ];

        $priorityCounts = collect($report['projects'])
            ->flatMap(fn ($project) => $project['tasks'])
            ->when($report['standalone'], fn ($tasks) => $tasks->concat($report['standalone']['tasks']))
            ->filter(fn ($task) => $task['completed_at'] !== null)
            ->countBy('priority');

        $priorityChips = collect(['urgent', 'high', 'medium', 'low'])
            ->filter(fn ($priority) => ($priorityCounts[$priority] ?? 0) > 0)
            ->map(fn ($priority) => [
                'priority' => $priority,
                'label' => self::PRIORITY_LABELS[$priority],
                'classes' => self::PRIORITY_CHIP_CLASSES[$priority],
                'count' => $priorityCounts[$priority],
            ])
            ->values();

        return ['tiles' => $tiles, 'plainTiles' => $plainTiles, 'priorityChips' => $priorityChips];
    }

    public function highlightCards(array $report): array
    {
        $kinds = [
            'hardest_close' => ['icon' => 'fitness_center', 'label' => 'سخت‌ترین وظیفهٔ بسته‌شده'],
            'fastest_turnaround' => ['icon' => 'bolt', 'label' => 'سریع‌ترین انجام'],
            'most_collaborated' => ['icon' => 'groups', 'label' => 'بیشترین همکاری'],
        ];

        return collect($report['highlights'])
            ->only(array_keys($kinds))
            ->filter()
            ->map(fn (array $item, string $kind) => $kinds[$kind] + ['kind' => $kind, 'item' => $item])
            ->values()
            ->all();
    }

    private function spark(mixed $previous, mixed $current): ?array
    {
        return ($previous !== null && $current !== null) ? [max(0, (float) $previous), max(0, (float) $current)] : null;
    }

    public function windowStatement(Carbon $start, Carbon $end): string
    {
        $sameYear = toJalali($start, 'Y') === toJalali($end, 'Y');

        return 'بازهٔ ' . toJalali($start, $sameYear ? 'j F' : 'j F Y') . ' تا ' . toJalali($end, 'j F Y');
    }

    public function deltaChip(?float $current, ?float $previous, string $suffix = '%'): ?array
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $diff = $current - $previous;

        $direction = match (true) {
            abs($diff) < self::DELTA_EPSILON => 'flat',
            $diff > 0 => 'up',
            default => 'down',
        };

        return [
            'direction' => $direction,
            'text' => 'قبلی: ' . $this->formatNumber($previous) . $suffix,
            'icon' => match ($direction) {
                'up' => 'trending_up',
                'down' => 'trending_down',
                default => 'trending_flat',
            },
            'classes' => 'text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-highest)]',
        ];
    }

    public function roleBadge(string $role): array
    {
        return match ($role) {
            'owner' => [
                'icon' => 'stars',
                'label' => 'مالک',
                'classes' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            ],
            'member' => [
                'icon' => 'group',
                'label' => 'عضو',
                'classes' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            ],
            default => [
                'icon' => 'person',
                'label' => 'همکار',
                'classes' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            ],
        };
    }

    public function projectHealthChip(array $projectRow): ?array
    {
        $stillOverdue = $projectRow['still_overdue'] ?? 0;
        $isArchived = (bool) ($projectRow['is_archived'] ?? false);

        if ($stillOverdue < 1 && !$isArchived) {
            return null;
        }

        if ($isArchived) {
            return [
                'icon' => 'inventory_2',
                'text' => 'آرشیو شده',
                'classes' => self::NEUTRAL_CHIP_CLASSES,
            ];
        }

        return [
            'icon' => 'schedule',
            'text' => $stillOverdue . ' مورد دیرکرد در این پروژه',
            'classes' => $this->riskToneClasses($stillOverdue >= 3 ? 'error' : 'warning'),
        ];
    }

    private function formatNumber(float $value): string
    {
        return convertToPersian(rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.'));
    }
}
