<?php

namespace App\Livewire\Dashboard\Project\Presentation;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Project;
use App\Traits\BuildsMessageGroups;
use App\Traits\RiskEscalationChip;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Morilog\Jalali\Jalalian;
use Throwable;

class ProjectPresenter
{
    use BuildsMessageGroups;
    use RiskEscalationChip;

    private const LIFECYCLE_LEGEND = [
        'start' => [
            'colorClass' => 'bg-[var(--md-sys-color-success)]',
            'iconColorClass' => 'text-[var(--md-sys-color-success)]',
            'chipClass' => 'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)]',
            'label' => 'ایجاد', 'icon' => 'add_circle',
        ],
        'change' => [
            'colorClass' => 'bg-[var(--md-sys-color-warning)]',
            'iconColorClass' => 'text-[var(--md-sys-color-warning)]',
            'chipClass' => 'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]',
            'label' => 'تغییر وضعیت', 'icon' => 'sync_alt',
        ],
        'deadline' => [
            'colorClass' => 'bg-[var(--md-sys-color-error)]',
            'iconColorClass' => 'text-[var(--md-sys-color-error)]',
            'chipClass' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
            'label' => 'مهلت', 'icon' => 'schedule',
        ],
        'completed' => [
            'colorClass' => 'bg-[var(--md-sys-color-primary)]',
            'iconColorClass' => 'text-[var(--md-sys-color-primary)]',
            'chipClass' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'label' => 'تکمیل', 'icon' => 'task_alt',
        ],
        'projectDeadline' => [
            'colorClass' => 'bg-[var(--md-sys-color-tertiary)]',
            'iconColorClass' => 'text-[var(--md-sys-color-tertiary)]',
            'chipClass' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'label' => 'مهلت پروژه', 'icon' => 'sports_score',
        ],
    ];

    private const RESOLVED_CHIP_CLASS = 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]';

    public function lifecycleLegend(): array
    {
        return self::LIFECYCLE_LEGEND;
    }

    public function lifecycleEventData(array $event): array
    {
        $meta = self::LIFECYCLE_LEGEND[$event['marker']];
        $isResolvedDeadline = $event['marker'] === 'deadline' && ($event['isResolved'] ?? false);

        return [
            'colorClass' => $isResolvedDeadline ? 'bg-[var(--md-sys-color-outline)]' : $meta['colorClass'],
            'iconColorClass' => $isResolvedDeadline ? 'text-[var(--md-sys-color-outline)]' : $meta['iconColorClass'],
            'chipClass' => $isResolvedDeadline ? self::RESOLVED_CHIP_CLASS : $meta['chipClass'],
            'icon' => $isResolvedDeadline ? 'task_alt' : $meta['icon'],
            'markerLabel' => $isResolvedDeadline ? 'مهلت (برطرف‌شده)' : $meta['label'],
            'badge' => '#' . convertToPersian($event['task_id']),
            'title' => $event['title'],
            'time' => $event['time'],
            'line' => match ($event['marker']) {
                'start' => 'وظیفه ایجاد شد.',
                'deadline' => ($isResolvedDeadline ? 'مهلت — برطرف‌شده' : 'سررسید مهلت') . $this->deadlineSlipSuffix($event),
                default => sprintf(
                    'وضعیت از «%s» به «%s» تغییر کرد.',
                    $this->taskStatusLabel($event['from'] ?? null),
                    $this->taskStatusLabel($event['to'] ?? null),
                ),
            },
            'pausedText' => isset($event['pausedMinutes']) ? $this->humanDuration($event['pausedMinutes']) : null,
        ];
    }

    private function deadlineSlipSuffix(array $event): string
    {
        $slip = $this->slipText($event);

        return $slip !== null ? ' • ' . $slip : '';
    }

    public function slipText(array $event): ?string
    {
        $count = (int)($event['slipCount'] ?? 0);
        if ($count <= 0) {
            return null;
        }

        $from = '';
        if (!empty($event['slipFrom'])) {
            try {
                $jalali = Jalalian::fromFormat('Y-m-d', (string)$event['slipFrom'])->format('j F Y');
                $from = ' (از ' . convertToPersian($jalali) . ')';
            } catch (Throwable) {
                $from = '';
            }
        }

        return 'جابجایی ×' . convertToPersian($count) . $from;
    }

    public function carryDateText(array $carry): string
    {
        try {
            return convertToPersian(Jalalian::fromFormat('Y-m-d', (string)$carry['date'])->format('j F Y'));
        } catch (Throwable) {
            return (string)$carry['date'];
        }
    }

    public function ganttRowData(array $row): array
    {
        $isDone = $row['isDone'];

        return [
            'barClass' => $isDone
                ? 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]'
                : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
            'fillClass' => 'bg-[var(--md-sys-color-primary)]',
            'slipText' => $this->slipText($row),
            'titleAttr' => sprintf(
                '#%s %s — پیشرفت %s٪',
                convertToPersian($row['task_id']),
                $row['title'],
                convertToPersian($row['progressPct']),
            ),
        ];
    }

    private function taskStatusLabel(?string $value): string
    {
        return TaskStatus::tryFrom((string)$value)?->getLabel() ?? 'نامشخص';
    }

    private function humanDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return convertToPersian($minutes) . ' دقیقه';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return convertToPersian($hours) . ' ساعت';
        }

        return convertToPersian(intdiv($hours, 24)) . ' روز';
    }

    public function headerRiskChips(Project $project, array $summary): array
    {
        $chips = [];

        if (($summary['overdue'] ?? 0) > 0) {
            $chips[] = [
                'icon' => 'schedule',
                'text' => $summary['overdue'] . ' دیرکرد',
                'classes' => $this->riskToneClasses('error'),
            ];
        }

        if (($summary['at_risk'] ?? 0) > 0) {
            $chips[] = [
                'icon' => 'warning',
                'text' => $summary['at_risk'] . ' در معرض تأخیر',
                'classes' => $this->riskToneClasses('warning'),
            ];
        }

        if ($deadlineChip = $this->deadlineCountdownChip($project)) {
            $chips[] = $deadlineChip;
        }

        return $chips;
    }

    private function deadlineCountdownChip(Project $project): ?array
    {
        $deadlineSetting = $project->setting('deadline');
        if (!$deadlineSetting) {
            return null;
        }

        $days = (int)now()->startOfDay()->diffInDays(Carbon::parse($deadlineSetting)->startOfDay(), false);

        return match (true) {
            $days < 0 => [
                'icon' => 'event_busy',
                'text' => abs($days) . ' روز از مهلت پروژه گذشت',
                'classes' => $this->riskToneClasses('error'),
            ],
            $days === 0 => [
                'icon' => 'event_busy',
                'text' => 'امروز مهلت پروژه',
                'classes' => $this->riskToneClasses('error'),
            ],
            $days <= 7 => [
                'icon' => 'event_busy',
                'text' => $days . ' روز تا مهلت پروژه',
                'classes' => $this->riskToneClasses('warning'),
            ],
            default => [
                'icon' => 'event_busy',
                'text' => $days . ' روز تا مهلت پروژه',
                'classes' => $this->riskToneClasses('success'),
            ],
        };
    }

    public function defaultProjectFormTab(array $tabs, ViewErrorBag $errors): string
    {
        foreach ($tabs as $tab) {
            if ($errors->hasAny($tab['errors'])) {
                return $tab['key'];
            }
        }

        return $tabs[0]['key'] ?? 'details';
    }

    public function tabs(?int $activeProjectId): array
    {
        return [
            'report' => ['component' => 'dashboard.project.report', 'key' => 'tab-report-' . $activeProjectId, 'lazy' => true],
            'analytics' => ['component' => 'dashboard.project.analytics', 'key' => 'tab-analytics-' . $activeProjectId, 'lazy' => true],
            'activity' => ['component' => 'dashboard.project.activity', 'key' => 'tab-activity-' . $activeProjectId, 'lazy' => true],
            'projectCalendar' => ['component' => 'dashboard.project.calendar', 'key' => 'tab-projectCalendar-' . $activeProjectId, 'lazy' => true],
            'kanban' => ['component' => 'dashboard.project.kanban', 'key' => 'tab-kanban-' . $activeProjectId, 'lazy' => true],
        ];
    }

    public function projectFormTabs(): array
    {
        return [
            [
                'key' => 'details',
                'label' => 'جزئیات و اعضا',
                'icon' => 'workspaces',
                'errors' => ['projectForm.name', 'projectForm.memberIds'],
            ],
            [
                'key' => 'departments',
                'label' => 'دپارتمان‌ها',
                'icon' => 'corporate_fare',
                'errors' => ['projectForm.departments'],
            ],
            [
                'key' => 'settings',
                'label' => 'تنظیمات',
                'icon' => 'tune',
                'errors' => [
                    'projectForm.requiresApproval',
                    'projectForm.slaHours',
                    'projectForm.deadlineYear',
                    'projectForm.deadlineMonth',
                    'projectForm.deadlineDay',
                    'projectForm.customSchema',
                ],
            ],
        ];
    }

    private const LABEL_TONES = ['amethyst', 'sapphire', 'sage', 'gold'];

    private const URGENCY_CHIP_CLASSES = [
        'overdue' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
        'due' => 'bg-[var(--md-sys-color-error-container)]/60 text-[var(--md-sys-color-on-error-container)]',
        'idle' => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]',
    ];

    public function groupedReportRows(array $rows): Collection
    {
        return collect($rows)
            ->groupBy(fn($row) => $row['department'] ?: '')
            ->sortBy(fn($rows, $key) => $key === '' ? 1 : 0);
    }

    public function reportRowFlag(array $row): array
    {
        $kind = $row['urgency']['kind'] ?? null;

        return [
            'kind' => $kind,
            'overdue' => $kind === 'overdue' && $row['status'] !== 'done',
            'due' => $kind === 'due',
            'idle' => $kind === 'idle',
        ];
    }

    public function activityPairs(array $entries): array
    {
        return collect($entries)
            ->map(fn($e) => [$e['id'], strip_tags($e['body_html'] ?? $e['body'] ?? ''), $e['user_name'], $e['created_at'], $e['type']])
            ->values()
            ->all();
    }

    public function reactionGroups(?array $reactions): Collection
    {
        return collect($reactions ?? [])->groupBy('emoji');
    }

    public function reportRowDetail(array $row): array
    {
        $kind = $row['urgency']['kind'] ?? null;

        $labels = [];
        foreach ($row['labels'] ?? [] as $label) {
            $labels[] = ['label' => $label, 'tone' => self::LABEL_TONES[crc32($label) % 4]];
        }

        return [
            'kind' => $kind,
            'replies' => (int)($row['replies_count'] ?? 0),
            'attachments' => (int)($row['attachments_count'] ?? 0),
            'checkTotal' => (int)($row['checklist']['total'] ?? 0),
            'checkDone' => (int)($row['checklist']['done'] ?? 0),
            'ticketId' => $row['ticket_id'] ?? null,
            'urgClass' => self::URGENCY_CHIP_CLASSES[$kind] ?? self::RESOLVED_CHIP_CLASS,
            'labels' => $labels,
        ];
    }
}
