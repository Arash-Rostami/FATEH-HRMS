<?php

namespace App\Livewire\Dashboard\Tasksheet\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Livewire\Dashboard\Tasksheet\Presentation\TasksheetPresenter;
use App\Models\User;
use App\Services\ProjectTask\TasksheetService;
use Carbon\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTasksheetAction
{
    private const ROLE_LABELS = ['owner' => 'مالک', 'member' => 'عضو', 'collaborator' => 'همکار'];

    public function execute(User $subject, Carbon $start, Carbon $end): StreamedResponse
    {
        $report = app(TasksheetService::class)->report($subject, $start, $end);
        $presenter = new TasksheetPresenter();

        return response()->streamDownload(
            function () use ($subject, $report, $presenter, $start, $end) {
                $path = tempnam(sys_get_temp_dir(), 'fateh_tasksheet_');

                try {
                    $writer = new Writer();
                    $writer->openToFile($path);

                    $boldStyle = (new Style())->setFontBold();

                    $writer->addRow(Row::fromValues(['گزارش عملکرد ' . $subject->name], $boldStyle));
                    $writer->addRow(Row::fromValues([$presenter->windowStatement($start, $end)]));
                    $writer->addRow(Row::fromValues(['']));

                    $this->writeScorecard($writer, $report['scorecard'], $boldStyle);
                    $writer->addRow(Row::fromValues(['']));

                    $writer->addRow(Row::fromValues([$report['narrative']]));
                    $writer->addRow(Row::fromValues(['']));

                    $this->writeHighlights($writer, $report['highlights'], $boldStyle);
                    $this->writeWeeklyTrend($writer, $report['weekly_totals'], $boldStyle);
                    $this->writeProjectBreakdown($writer, $report['projects'], $report['standalone'], $boldStyle);
                    $this->writeTaskDetails($writer, $report['projects'], $report['standalone'], $boldStyle);

                    $writer->close();
                    readfile($path);
                } finally {
                    @unlink($path);
                }
            },
            'tasksheet-' . $subject->id . '-' . $start->format('Y-m-d') . '-to-' . $end->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function writeScorecard(Writer $writer, array $sc, Style $boldStyle): void
    {
        $writer->addRow(Row::fromValues(['خلاصه عملکرد'], $boldStyle));
        $writer->addRow(Row::fromValues(['شاخص', 'مقدار', 'بازهٔ قبل', 'تغییر (٪)'], $boldStyle));

        $rows = [
            ['تکمیل‌شده', $sc['completed']['value'], $sc['completed']['previous'], $sc['completed']['delta_percent']],
            ['به‌موقع (٪)', $sc['on_time_percent']['value'], $sc['on_time_percent']['previous'], $sc['on_time_percent']['delta_percent']],
            ['میانگین مدت انجام (روز)', $sc['cycle_time_days']['avg'], null, null],
            ['میانهٔ مدت انجام (روز)', $sc['cycle_time_days']['median'], $sc['cycle_time_days']['previous_median'], $sc['cycle_time_days']['delta_percent']],
            ['تأییدیهٔ دریافتی', $sc['approvals_received']['value'], $sc['approvals_received']['previous'], $sc['approvals_received']['delta_percent']],
        ];

        foreach ($rows as [$label, $value, $previous, $deltaPercent]) {
            $writer->addRow(Row::fromValues([
                $label,
                $value ?? '—',
                $previous ?? '—',
                $deltaPercent !== null ? $deltaPercent . '٪' : '—',
            ]));
        }

        $writer->addRow(Row::fromValues(['همچنان معوق', $sc['still_overdue']]));
        $writer->addRow(Row::fromValues(['در حال انجام', $sc['in_progress']]));
        $writer->addRow(Row::fromValues(['با مهلت نزدیک', $sc['upcoming_deadline']]));
    }

    private function writeHighlights(Writer $writer, array $highlights, Style $boldStyle): void
    {
        $rows = array_filter([
            $highlights['hardest_close'] ? ['سخت‌ترین بسته‌شده', $highlights['hardest_close']] : null,
            $highlights['fastest_turnaround'] ? ['سریع‌ترین انجام', $highlights['fastest_turnaround']] : null,
            $highlights['most_collaborated'] ? ['بیشترین همکاری', $highlights['most_collaborated']] : null,
        ]);

        if ($rows === []) {
            return;
        }

        $writer->addRow(Row::fromValues(['نکات برجسته'], $boldStyle));

        foreach ($rows as [$label, $row]) {
            $extra = array_key_exists('comments_count', $row)
                ? ($row['comments_count'] ?? 0) . ' نظر'
                : number_format($row['cycle_time_days'], 1) . ' روز';

            $writer->addRow(Row::fromValues([$label, $row['title'] ?? '', $extra]));
        }

        $writer->addRow(Row::fromValues(['']));
    }

    private function writeWeeklyTrend(Writer $writer, array $weeklyTotals, Style $boldStyle): void
    {
        if ($weeklyTotals === []) {
            return;
        }

        $writer->addRow(Row::fromValues(['روند هفتگی تکمیل'], $boldStyle));
        $writer->addRow(Row::fromValues(array_map(fn($i) => 'هفتهٔ ' . ($i + 1), array_keys($weeklyTotals)), $boldStyle));
        $writer->addRow(Row::fromValues($weeklyTotals));
        $writer->addRow(Row::fromValues(['']));
    }

    private function writeProjectBreakdown(Writer $writer, array $projects, ?array $standalone, Style $boldStyle): void
    {
        if ($projects === [] && !$standalone) {
            return;
        }

        $writer->addRow(Row::fromValues(['خلاصه به تفکیک پروژه'], $boldStyle));
        $writer->addRow(Row::fromValues(['پروژه', 'نقش', 'تکمیل‌شده', 'به‌موقع (٪)', 'معوق', 'در حال انجام'], $boldStyle));

        foreach ($projects as $project) {
            $writer->addRow(Row::fromValues([
                $project['project_name'] ?? 'بدون نام',
                self::ROLE_LABELS[$project['role']] ?? $project['role'],
                $project['completed'],
                $project['on_time_percent'] !== null ? $project['on_time_percent'] . '٪' : '—',
                $project['still_overdue'],
                $project['in_progress'],
            ]));
        }

        if ($standalone) {
            $writer->addRow(Row::fromValues([
                'بدون پروژه', '—',
                $standalone['completed'],
                $standalone['on_time_percent'] !== null ? $standalone['on_time_percent'] . '٪' : '—',
                $standalone['still_overdue'],
                $standalone['in_progress'],
            ]));
        }

        $writer->addRow(Row::fromValues(['']));
    }

    private function writeTaskDetails(Writer $writer, array $projects, ?array $standalone, Style $boldStyle): void
    {
        $groups = array_map(fn(array $p) => ['name' => $p['project_name'] ?? 'بدون نام', 'tasks' => $p['tasks']], $projects);

        if ($standalone) {
            $groups[] = ['name' => 'بدون پروژه', 'tasks' => $standalone['tasks']];
        }

        if ($groups === []) {
            return;
        }

        $writer->addRow(Row::fromValues(['جزئیات وظایف'], $boldStyle));

        foreach ($groups as $group) {
            if ($group['tasks'] === []) {
                continue;
            }

            $writer->addRow(Row::fromValues([$group['name']], $boldStyle));
            $writer->addRow(Row::fromValues([
                'شناسه', 'عنوان', 'اولویت', 'وضعیت', 'مهلت', 'تاریخ تکمیل', 'مدت انجام (روز)', 'به‌موقع',
            ], $boldStyle));

            foreach ($group['tasks'] as $row) {
                $writer->addRow(Row::fromValues([
                    $row['task_id'],
                    $row['title'] ?? '',
                    TaskPriority::tryFrom((string) $row['priority'])?->getLabel() ?? '—',
                    TaskStatus::tryFrom((string) $row['status'])?->getLabel() ?? ($row['status'] ?? '—'),
                    $row['deadline'] ? toJalali($row['deadline'], 'Y/m/d') : '—',
                    $row['completed_at'] ? toJalali($row['completed_at'], 'Y/m/d') : '—',
                    $row['cycle_time_days'] !== null ? number_format($row['cycle_time_days'], 1) : '—',
                    match ($row['on_time'] ?? null) {
                        true => 'بله',
                        false => 'خیر',
                        default => '—',
                    },
                ]));
            }

            $writer->addRow(Row::fromValues(['']));
        }
    }
}
