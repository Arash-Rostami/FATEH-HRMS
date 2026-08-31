<?php

namespace App\Livewire\Dashboard\TaskBoard;

use App\Livewire\Dashboard\TaskBoard\Actions\ArchiveTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\AssignTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkAssignTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkDeleteTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkMoveTasksAction;
use App\Services\ProjectTask\CyclePriorityAction;
use App\Services\ProjectTask\LastTouchResolver;
use App\Livewire\Dashboard\TaskBoard\Actions\DeleteTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\ExportTasksAction;
use App\Services\ProjectTask\ReorderTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UnarchiveTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UndoTaskAssignmentAction;
use App\Services\ProjectTask\UpdateTaskStatusAction;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Livewire\Dashboard\Dms\Presentation\DmsPresenter;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Services\ProjectTask\ReportingService;
use App\Support\TaskAccessPolicy;
use App\Traits\FocusOnRecord;
use App\Traits\ManagesTaskModal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Lazy]
class Main extends Component
{
    use FocusOnRecord;
    use ManagesTaskModal;
    use WithFileUploads;

    private const DONE_WINDOW_DAYS = 45;
    public array $tasks = ['todo' => [], 'in-progress' => [], 'pending' => [], 'done' => []];
    public array $totalCount = ['todo' => 0, 'in-progress' => 0, 'pending' => 0, 'done' => 0];
    public array $doneTotalCount = ['done' => 0];
    public array $page = ['todo' => 1, 'in-progress' => 1, 'pending' => 1, 'done' => 1];
    #[Url(as: 'tab')]
    public string $activeTab = 'my-tasks';
    public int $perPage = 4;
    public bool $showAllDone = false;
    public bool $showArchived = false;
    public int $archivedCount = 0;
    public array $columns = ['todo', 'in-progress', 'pending', 'done'];
    public array $columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'updated_at', 'archived_at', 'approved_at', 'user_id', 'assigned_to', 'ticket_id', 'project_id', 'labels', 'priority', 'rank'];
    public array $relationsToLoad = ['assignee:id,name', 'creator:id,name', 'project:id,name,owner_id,settings', 'detail:id,task_id,checklist,attachments,state,collaborators,responsible_user_id,department_id,project,unit,section,meta,action_source,action_source_domain', 'detail.responsibleUser:id,name'];
    #[Url(as: 'q', except: '')]
    public string $search = '';
    #[Url(as: 'deadline', except: '')]
    public string $deadlineFilter = '';
    #[Url(as: 'project')]
    public ?int $projectFilter = null;
    #[Url(as: 'priority', except: '')]
    public string $priorityFilter = '';
    #[Url(as: 'label', except: [])]
    public array $labelFilter = [];
    #[Url(as: 'responsible')]
    public ?int $responsibleFilter = null;
    #[Url(as: 'department')]
    public ?string $departmentFilter = null;
    #[Url(as: 'scheme', except: '')]
    public string $schemeFilter = '';
    #[Url(as: 'unit')]
    public ?string $unitFilter = null;
    #[Url(as: 'section')]
    public ?string $sectionFilter = null;
    #[Url(as: 'assignee')]
    public ?int $assigneeFilter = null;
    #[Url(as: 'actionSourceDomain', except: '')]
    public string $actionSourceDomainFilter = '';
    #[Url(as: 'actionSource', except: '')]
    public string $actionSourceFilter = '';
    public bool $selectionMode = false;

    public array $selectedTasks = [];

    public function addLabelFilter(string $label): void
    {
        if (in_array($label, $this->labelFilter, true)) return;

        $this->labelFilter[] = $label;
        $this->updatedLabelFilter();
    }

    public function toggleLabelFilter(string $label): void
    {
        $this->labelFilter = in_array($label, $this->labelFilter, true)
            ? array_values(array_diff($this->labelFilter, [$label]))
            : [...$this->labelFilter, $label];

        $this->updatedLabelFilter();
    }

    public function archiveTask(ArchiveTaskAction $action, int $taskId): void
    {
        if (!$action->execute($taskId)) return;

        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه آرشیو شد.', type: 'success');
    }

    public function assignTask(AssignTaskAction $action, int $taskId, ?int $userId): void
    {
        $task = $action->execute($taskId, $userId);

        if (!$task) return;

        if ($userId && $userId !== auth()->id()) {
            $this->switchTab('assigned-tasks');
        } else {
            $this->loadTasks();
        }

        $this->dispatch('toast', message: 'وظیفه با موفقیت ارجاع داده شد.', type: 'success');
    }

    public function bulkAssign(BulkAssignTasksAction $action, ?int $userId): void
    {
        $action->execute($this->selectedTasks, $userId);
        $this->selectedTasks = [];
        $this->loadTasks();
        $this->dispatch('toast', message: 'وظایف انتخاب‌شده با موفقیت ارجاع داده شدند.', type: 'success');
    }

    public function bulkDelete(BulkDeleteTasksAction $action): void
    {
        $action->execute($this->selectedTasks);
        $this->selectedTasks = [];
        $this->loadTasks();
        $this->dispatch('toast', message: 'وظایف انتخاب‌شده با موفقیت حذف شدند.', type: 'success');
    }

    public function bulkMoveStatus(BulkMoveTasksAction $action, string $status): void
    {
        $result = $action->execute($this->selectedTasks, $status);
        $this->selectedTasks = [];
        $this->loadTasks();

        if ($result['skipped'] > 0) {
            $this->dispatch('toast', message: convertToPersian($result['skipped']) . ' وظیفه رد شد — تعیین تکلیف مشخص نشده.', type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'وظایف انتخاب‌شده با موفقیت منتقل شدند.', type: 'success');
    }

    public function cyclePriority(CyclePriorityAction $action, int $taskId): void
    {
        if ($action->execute($taskId)) $this->loadTasks();
    }

    #[Computed]
    public function deadlineFilterCounts(): array
    {
        return app(ReportingService::class)->boardDeadlineCounts($this->scopedQuery(withDeadlineFilter: false));
    }

    #[Computed]
    public function isMyTasks(): bool
    {
        return $this->activeTab === 'my-tasks';
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return collect([
            $this->deadlineFilter !== '',
            $this->projectFilter !== null,
            $this->priorityFilter !== '',
            $this->labelFilter !== [],
            $this->responsibleFilter !== null,
            $this->departmentFilter !== null,
            $this->schemeFilter !== '',
            $this->unitFilter !== null,
            $this->sectionFilter !== null,
            $this->assigneeFilter !== null,
            $this->actionSourceDomainFilter !== '',
            $this->actionSourceFilter !== '',
        ])->filter()->count();
    }

    public function clearFilters(): void
    {
        $this->deadlineFilter = '';
        $this->projectFilter = null;
        $this->priorityFilter = '';
        $this->labelFilter = [];
        $this->responsibleFilter = null;
        $this->departmentFilter = null;
        $this->schemeFilter = '';
        $this->unitFilter = null;
        $this->sectionFilter = null;
        $this->assigneeFilter = null;
        $this->actionSourceDomainFilter = '';
        $this->actionSourceFilter = '';

        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function deleteTask(DeleteTaskAction $action, int $taskId): void
    {
        if (!$action->execute($taskId)) return;

        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه با موفقیت حذف شد.', type: 'success');
    }

    public function exportTasks(ExportTasksAction $action): StreamedResponse
    {
        return $action->execute((int)auth()->id());
    }

    protected function restoreAfterFocus(): void
    {
        $this->loadTasks();
    }

    public function jumpToPage(string $column, int $target): void
    {
        if (!isset($this->page[$column])) return;

        $lastPage = max(1, (int)ceil($this->totalCount[$column] / $this->perPage));
        $this->page[$column] = max(1, min($target, $lastPage));
        $this->loadTasks();
    }

    private function personalTaskScope(): \Closure
    {
        $userId = auth()->id();

        return fn(Builder $q) => $q->where('assigned_to', $userId)->orWhere('user_id', $userId);
    }

    #[Computed]
    public function schemeOptions(): array
    {
        return app(ReportingService::class)->boardSchemeOptions($this->personalTaskScope(), 'u' . auth()->id());
    }

    #[Computed]
    public function unitOptions(): array
    {
        return app(ReportingService::class)->boardUnitOptions($this->personalTaskScope(), 'u' . auth()->id());
    }

    #[Computed]
    public function sectionOptions(): array
    {
        return app(ReportingService::class)->boardSectionOptions($this->personalTaskScope(), 'u' . auth()->id());
    }

    #[Computed]
    public function assigneeOptions(): array
    {
        return app(ReportingService::class)->boardAssigneeOptions($this->personalTaskScope(), 'u' . auth()->id());
    }

    #[Computed]
    public function actionSourceDomainOptions(): array
    {
        return app(ReportingService::class)->boardActionSourceDomainOptions($this->personalTaskScope(), 'u' . auth()->id());
    }

    #[Computed]
    public function actionSourceOptions(): array
    {
        return app(ReportingService::class)->boardActionSourceOptions(
            $this->personalTaskScope(),
            'u' . auth()->id(),
            $this->actionSourceDomainFilter !== '' ? $this->actionSourceDomainFilter : null
        );
    }

    public function loadTasks(): void
    {
        $baseQuery = $this->scopedQuery();

        $doneWindow = $this->doneWindowScope();
        $archivedScope = $this->archivedScope();

        $counts = (clone $baseQuery)
            ->whereIn('status', array_diff($this->columns, [TaskStatus::Done->value]))
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        $doneQuery = (clone $baseQuery)->where('status', TaskStatus::Done->value)->tap($archivedScope);

        $this->archivedCount = (clone $baseQuery)->where('status', TaskStatus::Done->value)->whereNotNull('archived_at')->count();

        if ($doneWindow === null) {
            $count = $doneQuery->count();
            $this->doneTotalCount['done'] = $count;
            $this->totalCount['done'] = $count;
        } else {
            $threshold = now()->subDays(self::DONE_WINDOW_DAYS);
            $row = $doneQuery->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN updated_at >= ? THEN 1 ELSE 0 END) as windowed',
                [$threshold]
            )->first();
            $this->doneTotalCount['done'] = (int)($row->total ?? 0);
            $this->totalCount['done'] = (int)($row->windowed ?? 0);
        }

        $columnResults = [];

        foreach ($this->columns as $column) {
            $isDone = $column === TaskStatus::Done->value;

            if (!$isDone) {
                $this->totalCount[$column] = $counts->get($column, 0);
            }

            $columnResults[$column] = (clone $baseQuery)
                ->where('status', $column)
                ->when($isDone, $archivedScope)
                ->when($isDone && $doneWindow, $doneWindow)
                ->when($isDone, fn($q) => $q->orderBy($this->showArchived ? 'archived_at' : 'updated_at', 'desc'))
                ->when(!$isDone, fn($q) => $q->orderByRaw('rank IS NULL, rank')->orderBy('created_at', 'desc'))
                ->skip(($this->page[$column] - 1) * $this->perPage)
                ->take($this->perPage)
                ->withCount('replies')
                ->get($this->columnsToSelect);
        }

        $allTasks = new EloquentCollection(array_merge(...array_values(array_map(fn($c) => $c->all(), $columnResults))));
        $allTasks->load($this->relationsToLoad);

        $collaboratorLookup = $this->collaboratorLookup($allTasks);
        $lastTouchLookup = app(LastTouchResolver::class)->resolve($allTasks->pluck('id')->all());

        foreach ($this->columns as $column) {
            $this->tasks[$column] = $columnResults[$column]->map(function (Task $task) use ($collaboratorLookup, $lastTouchLookup) {
                $data = $task->toArray();

                if ($data['detail'] !== null) {
                    $data['detail']['collaborator_users'] = array_values(array_filter(array_map(
                        static fn($id) => $collaboratorLookup[$id] ?? null,
                        $task->detail->collaborators ?? []
                    )));
                }

                $data['last_touched'] = $lastTouchLookup[$task->id] ?? null;

                return $data;
            })->all();
        }
    }

    public function mount(): void
    {
        $this->loadTasks();
    }

    public function nextPage(string $column): void
    {
        if ($this->page[$column] < ceil($this->totalCount[$column] / $this->perPage)) {
            $this->page[$column]++;
            $this->loadTasks();
        }
    }

    public function prevPage(string $column): void
    {
        if ($this->page[$column] > 1) {
            $this->page[$column]--;
            $this->loadTasks();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.taskboard', ['presenter' => new TaskBoardPresenter(), 'dmsPresenter' => new DmsPresenter()])
            ->extends('layouts.app')
            ->section('content');
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.taskboard.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    public function reorderTask(ReorderTaskAction $action, int $taskId, ?int $beforeTaskId, ?string $targetStatus = null): void
    {
        $task = Task::query()->forUser(auth()->id())->with('detail:id,task_id,state')->find($taskId);

        if (!$task) return;

        if ($targetStatus === TaskStatus::Done->value && empty($task->detail?->state)) {
            $this->dispatch('toast', message: 'برای انتقال به «انجام‌شده» ابتدا تعیین تکلیف را مشخص کنید.', type: 'error');
            return;
        }

        $action->execute($task, $beforeTaskId, $targetStatus);
        $this->loadTasks();
    }

    public function setDeadlineFilter(string $filter): void
    {
        if (!in_array($filter, ['', 'overdue', 'today', 'week'], true)) {
            return;
        }

        $this->deadlineFilter = $this->deadlineFilter === $filter ? '' : $filter;
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->page = array_fill_keys($this->columns, 1);
        $this->showAllDone = false;
        $this->showArchived = false;
        $this->selectedTasks = [];
        $this->loadTasks();
    }

    public function toggleSelectionMode(): void
    {
        $this->selectionMode = !$this->selectionMode;
        $this->selectedTasks = [];
    }

    public function toggleShowAllDone(): void
    {
        $this->showAllDone = !$this->showAllDone;
        $this->showArchived = false;
        $this->page['done'] = 1;
        $this->loadTasks();
    }

    public function toggleShowArchived(): void
    {
        $this->showArchived = !$this->showArchived;
        $this->showAllDone = false;
        $this->page['done'] = 1;
        $this->loadTasks();
    }

    public function toggleTaskSelection(int $taskId): void
    {
        if (in_array($taskId, $this->selectedTasks, true)) {
            $this->selectedTasks = array_values(array_diff($this->selectedTasks, [$taskId]));
        } else {
            $this->selectedTasks[] = $taskId;
        }
    }

    public function unarchiveTask(UnarchiveTaskAction $action, int $taskId): void
    {
        if (!$action->execute($taskId)) return;

        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه از آرشیو خارج شد.', type: 'success');
    }

    public function undoAssignment(UndoTaskAssignmentAction $action, int $taskId): void
    {
        if ($action->execute($taskId)) {
            $this->switchTab('my-tasks');
        }
    }

    public function updateTaskStatus(UpdateTaskStatusAction $action, int $taskId, string $newColumn): void
    {
        if ($newColumn === TaskStatus::Done->value && empty(TaskDetail::where('task_id', $taskId)->value('state'))) {
            $this->dispatch('toast', message: 'برای انتقال به «انجام‌شده» ابتدا تعیین تکلیف را مشخص کنید.', type: 'error');
            return;
        }

        if ($action->execute($taskId, $newColumn)) $this->loadTasks();
    }

    public function updatedLabelFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedDeadlineFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedPriorityFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedProjectFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedResponsibleFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedSchemeFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedUnitFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedSectionFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedAssigneeFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedActionSourceDomainFilter(): void
    {
        $this->actionSourceFilter = '';
        unset($this->actionSourceOptions);
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedActionSourceFilter(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    public function updatedSearch(): void
    {
        $this->page = array_fill_keys($this->columns, 1);
        $this->loadTasks();
    }

    protected function recordFocusType(): string
    {
        return 'task';
    }

    protected function refreshAfterTaskSave(): void
    {
        $this->loadTasks();
    }

    private function archivedScope(): callable
    {
        return function (Builder $query) {
            if ($this->search !== '') {
                return;
            }

            $query->when(
                $this->showArchived,
                fn(Builder $q) => $q->whereNotNull('archived_at'),
                fn(Builder $q) => $q->whereNull('archived_at'),
            );
        };
    }

    private function deadlineFilterScope(?string $filter = null): callable
    {
        return match ($filter ?? $this->deadlineFilter) {
            'overdue' => fn(Builder $q) => $q->where('deadline', '<', now()->startOfDay()),
            'today' => fn(Builder $q) => $q->whereDate('deadline', now()->toDateString()),
            'week' => fn(Builder $q) => $q->whereBetween('deadline', [
                now()->startOfWeek(Carbon::SATURDAY),
                now()->startOfWeek(Carbon::SATURDAY)->addDays(6)->endOfDay(),
            ]),
            default => fn(Builder $q) => $q,
        };
    }

    private function doneWindowScope(): ?callable
    {
        if ($this->showAllDone || $this->showArchived || $this->search !== '' || $this->open) {
            return null;
        }

        $threshold = now()->subDays(self::DONE_WINDOW_DAYS);

        return fn(Builder $query) => $query->where('updated_at', '>=', $threshold);
    }

    private function scopedQuery(bool $withDeadlineFilter = true): Builder
    {
        $userId = auth()->id();

        if ($this->open) {
            $focusedTask = Task::with('detail')->find($this->open);
            $visible = $focusedTask && TaskAccessPolicy::canView($focusedTask, auth()->user());

            return Task::query()->where('id', $visible ? $this->open : 0);
        }

        return Task::query()
            ->when($this->activeTab === 'my-tasks', fn($q) => $q->where(fn($sub) => $sub
                ->where('assigned_to', $userId)
                ->orWhere(fn($sub2) => $sub2->where('user_id', $userId)->whereNull('assigned_to'))
            ))
            ->when($this->activeTab === 'assigned-tasks', fn($q) => $q
                ->where('user_id', $userId)
                ->whereNotNull('assigned_to')
                ->where('assigned_to', '!=', $userId)
            )
            ->when($this->search !== '', fn($q) => $q->where(fn($sub) => $sub
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            ))
            ->when($withDeadlineFilter && $this->deadlineFilter !== '', fn($q) => $q
                ->whereNotNull('deadline')
                ->whereNull('archived_at')
                ->where('status', '!=', TaskStatus::Done->value)
                ->tap($this->deadlineFilterScope()))
            ->when($this->projectFilter, fn($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->priorityFilter !== '', fn($q) => $q->where('priority', $this->priorityFilter))
            ->when(!empty($this->labelFilter), function (Builder $q) {
                foreach ($this->labelFilter as $label) {
                    $q->whereJsonContains('labels', $label);
                }
            })
            ->when(
                $this->responsibleFilter || $this->departmentFilter || $this->schemeFilter !== '' || $this->unitFilter || $this->sectionFilter || $this->actionSourceDomainFilter !== '' || $this->actionSourceFilter !== '',
                fn(Builder $q) => $q->whereHas('detail', fn(Builder $dq) => $dq
                    ->when($this->responsibleFilter, fn(Builder $q2) => $q2->where('responsible_user_id', $this->responsibleFilter))
                    ->when($this->departmentFilter, fn(Builder $q2) => $q2->where('department_id', $this->departmentFilter))
                    ->when($this->schemeFilter !== '', fn(Builder $q2) => $q2->where('scheme', $this->schemeFilter))
                    ->when($this->unitFilter, fn(Builder $q2) => $q2->where('unit', $this->unitFilter))
                    ->when($this->sectionFilter, fn(Builder $q2) => $q2->where('section', $this->sectionFilter))
                    ->when($this->actionSourceDomainFilter !== '', fn(Builder $q2) => $q2->where('action_source_domain', $this->actionSourceDomainFilter))
                    ->when($this->actionSourceFilter !== '', fn(Builder $q2) => $q2->where('action_source', $this->actionSourceFilter))
                )
            )
            ->when($this->assigneeFilter, fn($q) => $q->where('assigned_to', $this->assigneeFilter));
    }
}
