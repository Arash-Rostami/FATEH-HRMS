<?php

namespace App\Livewire\Dashboard\TaskBoard\Presentation;

use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\ViewErrorBag;

class TaskBoardPresenter
{
    public function priorityChip(?string $priority): ?array
    {
        if (!$enum = TaskPriority::tryFrom($priority ?? '')) {
            return null;
        }

        return [
            'label' => $enum->getLabel(),
            'isUrgent' => $enum === TaskPriority::Urgent,
            'class' => match ($enum) {
                TaskPriority::Urgent => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                TaskPriority::High => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                TaskPriority::Medium => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                TaskPriority::Low => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]',
            },
        ];
    }

    public function stateChip(?string $state): ?array
    {
        if (!$enum = TaskState::tryFrom($state ?? '')) {
            return null;
        }

        return [
            'label' => $enum->getLabel(),
            'icon' => match ($enum) {
                TaskState::Extension => 'calendar_month',
                TaskState::Suspension => 'pause_circle',
                TaskState::Completion => 'task_alt',
            },
            'class' => match ($enum) {
                TaskState::Extension => 'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]',
                TaskState::Suspension => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                TaskState::Completion => 'bg-[var(--md-sys-color-success-container)] text-[var(--md-sys-color-on-success-container)]',
            },
        ];
    }

    public function columnConfig(): array
    {
        static $config = null;

        return $config ??= [
            'todo'        => ['title' => TaskStatus::Todo->getLabel(),       'icon' => '🧾', 'color' => 'primary',   'lightGradient' => 'from-rose-500 to-pink-600',      'darkGradient' => 'from-rose-700 to-pink-800'],
            'in-progress' => ['title' => TaskStatus::InProgress->getLabel(), 'icon' => '⏳', 'color' => 'secondary', 'lightGradient' => 'from-amber-500 to-orange-600',    'darkGradient' => 'from-amber-700 to-orange-800'],
            'pending'     => ['title' => TaskStatus::Pending->getLabel(),    'icon' => '🛑', 'color' => 'error',     'lightGradient' => 'from-red-600 to-red-700',        'darkGradient' => 'from-red-800 to-red-900'],
            'done'        => ['title' => TaskStatus::Done->getLabel(),       'icon' => '🎯', 'color' => 'tertiary',  'lightGradient' => 'from-emerald-500 to-green-600',  'darkGradient' => 'from-emerald-700 to-green-800'],
        ];
    }

    public function columnState(string $status): array
    {
        $config = $this->columnConfig();

        return $config[$status] ?? $config['todo'];
    }

    public function emptyStateFlags(string $column, bool $showAllDone, bool $showArchived, string $search, array $doneTotalCount): array
    {
        $isDoneBase = $column === 'done' && $search === '';
        $windowActive = $isDoneBase && !$showAllDone && !$showArchived;

        return [
            'windowActive' => $windowActive,
            'olderExist' => $windowActive && !empty($doneTotalCount['done']),
            'archiveEmpty' => $isDoneBase && $showArchived,
        ];
    }

    public function defaultTaskFormTab(array $tabs, ViewErrorBag $errors): string
    {
        foreach ($tabs as $tab) {
            if ($errors->hasAny($tab['errors'])) {
                return $tab['key'];
            }
        }

        return $tabs[0]['key'] ?? 'content';
    }

    public function taskFormTabs(): array
    {
        static $tabs = null;

        return $tabs ??= [
            [
                'key' => 'content',
                'label' => 'جزئیات وظیفه',
                'icon' => 'edit_note',
                'errors' => ['form.newTitle', 'form.deadlineYear', 'form.deadlineMonth', 'form.deadlineDay', 'form.deadline', 'form.selectedAssignee', 'form.projectId', 'form.priority'],
            ],
            [
                'key' => 'followup',
                'label' => 'اقدام و پیگیری',
                'icon' => 'task_alt',
                'errors' => ['form.checklist', 'form.collaborators', 'form.state', 'form.attachments'],
            ],
            [
                'key' => 'reply',
                'label' => 'نظرات',
                'description' => 'فقط بین ایجادکننده، مسئول انجام و همکاران این وظیفه',
                'icon' => 'forum',
                'errors' => ['taskReplyForm.body', 'taskReplyForm.files'],
            ],
            [
                'key' => 'info',
                'label' => 'اطلاعات تکمیلی',
                'icon' => 'list_alt',
                'errors' => ['form.departmentId', 'form.unit', 'form.section', 'form.project', 'form.scheme', 'form.responsibleUserId', 'form.actionSourceDomain', 'form.actionSource', 'form.labels'],
            ],
            [
                'key' => 'meta',
                'label' => 'دیتای سفارشی',
                'icon' => 'sell',
                'errors' => ['form.meta'],
            ],
            [
                'key' => 'history',
                'label' => 'تاریخچه',
                'description' => 'زمان‌بندی تغییرات این وظیفه',
                'icon' => 'history',
                'errors' => [],
            ],
        ];
    }

    public function tabBadgeCounts(EloquentCollection $comments): array
    {
        return [
            'reply' => $comments->count(),
        ];
    }

    public function columnPagerMeta(int $taskCount, int $perPage, int $currentPage = 1): array
    {
        $lastPage = max($perPage > 0 ? (int) ceil($taskCount / $perPage) : 1, 1);

        return [
            'lastPage' => $lastPage,
            'isFirstPage' => $currentPage <= 1,
            'isLastPage' => $currentPage >= $lastPage,
        ];
    }

    public function replyAttachmentMeta(array $file): array
    {
        return [
            'url' => Reply::resolvePublicAssetUrl($file['path'] ?? null),
            'isImage' => str_starts_with($file['mime'] ?? '', 'image/'),
        ];
    }

    public function deadlineFilterOptions(array $dfCounts): array
    {
        return [
            ['value' => 'overdue', 'label' => 'سررسید گذشته', 'count' => $dfCounts['overdue'] ?? 0],
            ['value' => 'today', 'label' => 'امروز', 'count' => $dfCounts['today'] ?? 0],
            ['value' => 'week', 'label' => 'این هفته', 'count' => $dfCounts['week'] ?? 0],
        ];
    }

    public function toolbarSurface(): string
    {
        return 'rounded-xl bg-[var(--md-sys-color-surface)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent)]';
    }

    public function toolbarButtonClass(): string
    {
        return 'shrink-0 ripple-effect';
    }

    public function utilityClass(bool $active): string
    {
        return $active
            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_1px_3px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]'
            : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]';
    }

    public function columnButtonBase(): string
    {
        return 'ripple-effect w-7 h-7 rounded-lg transition-all duration-200 active:scale-95 flex items-center justify-center';
    }

    public function columnButtonIcon(): string
    {
        return 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]';
    }

    public function cardMeta(array $task, bool $isPersonalBoard, array $departmentOptions): array
    {
        $status = $task['status'];
        $detail = $task['detail'] ?? [];
        $taskId = $task['id'];

        $urgency = $task['urgency_state'] ?? ['score' => 0.0, 'kind' => null, 'label' => null];
        $checklist = $detail['checklist'] ?? [];
        $attachments = $detail['attachments'] ?? [];

        $freeTextProject = trim((string) ($detail['project'] ?? ''));
        $linkedProject = $task['project'] ?? null;
        $projectLabel = $freeTextProject !== ''
            ? $freeTextProject
            : ($linkedProject['name'] ?? (!empty($task['project_id']) ? '#'.$task['project_id'] : null));
        $projectHasLink = !empty($task['project_id']);

        $hasDeadline = !empty($task['deadline']);
        $deadlineTitle = null;

        if ($hasDeadline) {
            $titleParts = ['مهلت: ' . ($task['deadline_formatted'] ?? '')];

            if (isPast($task['deadline']) && $status !== 'done') {
                $titleParts[] = 'این وظیفه از مهلت گذشته است';
            } elseif (!empty($urgency['label'])) {
                $titleParts[] = $urgency['label'];
            }

            $deadlineTitle = implode("\n", $titleParts);
        }

        $isPendingApproval = $status === 'done'
            && ($task['approved_at'] ?? null) === null
            && (($task['project']['settings']['requires_approval'] ?? null) === true);

        $metaChips = [];
        foreach (($detail['meta'] ?? []) as $key => $value) {
            if (!is_scalar($value) || $value === null || $value === '') continue;
            $metaChips[] = [
                'key' => (string) $key,
                'label' => (string) ($task['project']['settings']['custom_schema'][$key]['label'] ?? $key),
                'value' => (string) $value,
            ];
        }

        $lastTouched = $task['last_touched'] ?? null;

        $actionSource = trim((string) ($detail['action_source'] ?? ''));
        $actionSourceDomain = trim((string) ($detail['action_source_domain'] ?? ''));
        $actionSourceChip = ($actionSource !== '' || $actionSourceDomain !== '') ? [
            'label' => $actionSource !== '' ? $actionSource : $actionSourceDomain,
            'source' => $actionSource,
            'domain' => $actionSourceDomain,
        ] : null;

        return [
            'urgency' => $urgency,
            'colState' => $this->columnState($status),
            'checklist' => $checklist,
            'checklistTotal' => count($checklist),
            'checklistDone' => count(array_filter($checklist, fn(array $item) => $item['done'] ?? false)),
            'attachments' => $attachments,
            'attachmentsCount' => count($attachments),
            'stateChip' => $this->stateChip($detail['state'] ?? null),
            'responsibleUser' => $detail['responsible_user'] ?? null,
            'departmentLabel' => $departmentOptions[$detail['department_id'] ?? ''] ?? null,
            'unit' => $detail['unit'] ?? null,
            'section' => $detail['section'] ?? null,
            'projectLabel' => $projectLabel,
            'projectHasLink' => $projectHasLink,
            'collaboratorUsers' => $detail['collaborator_users'] ?? [],
            'canCyclePriority' => !empty($task['can_change_status']) && empty($task['ticket_id']) && empty($task['is_archived']),
            'labelFilterMethod' => $isPersonalBoard ? 'addLabelFilter' : 'addKanbanLabelFilter',
            'hasDeadline' => $hasDeadline,
            'deadlineTitle' => $deadlineTitle,
            'isPending' => $status === 'pending',
            'isPendingApproval' => $isPendingApproval,
            'canApprove' => $isPendingApproval && ($task['project']['owner_id'] ?? null) === auth()->id(),
            'isUrgent' => ($urgency['score'] ?? 0) > 0,
            'metaChips' => $metaChips,
            'actionSourceChip' => $actionSourceChip,
            'lastTouchedBy' => $lastTouched ? [
                'user_name' => $lastTouched['user_name'],
                'ago' => toJalaliRelative($lastTouched['created_at']),
            ] : null,
            'isFavoriteExpression' => $isPersonalBoard ? "isFavorite({$taskId})" : 'false',
            'showTaskExpression' => $isPersonalBoard ? "!showFavoritesOnly || isFavorite({$taskId})" : 'true',
        ];
    }
}
