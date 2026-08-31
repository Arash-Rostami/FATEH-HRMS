<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTasksAction
{
    public function execute(int $userId): StreamedResponse
    {
        $scope = fn($q) => $q->where(fn($q2) => $q2
            ->where('assigned_to', $userId)
            ->orWhere('user_id', $userId));

        $taskIds = Task::query()->where($scope)->pluck('id');
        $collaboratorNames = $this->collaboratorNames($taskIds);

        return response()->streamDownload(
            function () use ($taskIds, $collaboratorNames) {
                $path = tempnam(sys_get_temp_dir(), 'fateh_tasks_');

                try {
                    $writer = new Writer();
                    $writer->openToFile($path);

                    $writer->addRow(Row::fromValues([
                        'شناسه', 'عنوان', 'توضیحات', 'وضعیت', 'اولویت',
                        'ایجادکننده', 'محول‌شده به', 'پروژه', 'برچسب‌ها', 'مهلت',
                        'دپارتمان', 'واحد', 'بخش', 'طرح',
                        'حوزهٔ منبع اقدام', 'منبع اقدام', 'جوابگو', 'همکاران',
                        'وضعیت جزئیات', 'پیشرفت چک‌لیست', 'پیوست‌ها',
                        'تاریخ ایجاد', 'بایگانی شده در',
                    ], (new Style())->setFontBold()));

                    Task::query()
                        ->whereKey($taskIds)
                        ->with(['creator', 'assignee', 'project', 'detail.department', 'detail.responsibleUser'])
                        ->lazyById(1000, 'id')
                        ->each(function (Task $task) use ($writer, $collaboratorNames) {
                            $detail = $task->detail;
                            $checklist = $detail?->checklist ?? [];
                            $done = count(array_filter($checklist, fn(array $item) => $item['done'] ?? false));

                            $writer->addRow(Row::fromValues([
                                $task->id,
                                $task->title ?? '',
                                $task->description ?? '',
                                TaskStatus::tryFrom($task->status)?->getLabel() ?? $task->status,
                                $task->priority?->getLabel() ?? '—',
                                $task->creator?->name ?? '',
                                $task->assignee?->name ?? '',
                                $task->project?->name ?? '',
                                implode('، ', $task->labels ?? []),
                                $task->deadline ? toJalali($task->deadline, 'Y/m/d') : '-',
                                $detail?->department?->name ?? '',
                                $detail?->unit ?? '',
                                $detail?->section ?? '',
                                $detail?->scheme ?? '',
                                $detail?->action_source_domain ?? '',
                                $detail?->action_source ?? '',
                                $detail?->responsibleUser?->name ?? '',
                                $this->collaboratorLabel($detail?->collaborators ?? [], $collaboratorNames),
                                $detail?->state ?? '',
                                $checklist ? "{$done}/" . count($checklist) : '-',
                                count($detail?->attachments ?? []),
                                $task->created_at ? toJalaliSmart($task->created_at) : '—',
                                $task->archived_at ? toJalaliSmart($task->archived_at) : '—',
                            ]));
                        });

                    $writer->close();
                    readfile($path);
                } finally {
                    @unlink($path);
                }
            },
            'tasks-' . now()->format('Y-m-d') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function collaboratorNames(Collection $taskIds): Collection
    {
        $ids = TaskDetail::query()
            ->whereIn('task_id', $taskIds)
            ->pluck('collaborators')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $ids
            ? User::whereKey($ids)->pluck('name', 'id')
            : collect();
    }

    private function collaboratorLabel(array $ids, Collection $names): string
    {
        $resolved = array_map(fn($id) => $names->get($id), $ids);

        return implode('، ', array_filter($resolved, fn($v) => $v !== null && $v !== ''));
    }
}