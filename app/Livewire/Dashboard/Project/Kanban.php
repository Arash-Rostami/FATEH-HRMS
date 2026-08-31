<?php

namespace App\Livewire\Dashboard\Project;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Livewire\Dashboard\Dms\Presentation\DmsPresenter;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProjectTask\BoardCollaboratorResolver;
use App\Services\ProjectTask\ApproveTaskAction;
use App\Services\ProjectTask\CyclePriorityAction;
use App\Services\ProjectTask\LastTouchResolver;
use App\Services\ProjectTask\ReorderTaskAction;
use App\Services\ProjectTask\ReportingService;
use App\Services\ProjectTask\UpdateTaskStatusAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UnarchiveTaskAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Defer]
class Kanban extends Component
{
    private const KANBAN_COLUMNS = ['todo', 'in-progress', 'pending', 'done'];
    private const KANBAN_COLUMNS_TO_SELECT = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'updated_at', 'archived_at', 'approved_at', 'user_id', 'assigned_to', 'ticket_id', 'project_id', 'labels', 'priority', 'rank'];
    private const KANBAN_RELATIONS = ['assignee:id,name', 'creator:id,name', 'project:id,name,owner_id,settings', 'detail:id,task_id,checklist,attachments,state,collaborators,responsible_user_id,department_id,meta,action_source,action_source_domain', 'detail.responsibleUser:id,name'];
    private const LABEL_OPTIONS_FRESH_SECONDS = 60;
    private const LABEL_OPTIONS_STALE_SECONDS = 300;

    #[Locked]
    public ?int $activeProjectId = null;

    public string $kanbanSearch = '';
    public string $kanbanDeadlineFilter = '';
    public string $kanbanPriorityFilter = '';
    public array $kanbanLabelFilter = [];
    public string $kanbanSchemeFilter = '';
    public ?string $kanbanUnitFilter = null;
    public ?string $kanbanSectionFilter = null;
    public ?string $kanbanDepartmentFilter = null;
    public ?int $kanbanResponsibleFilter = null;
    public ?int $kanbanAssigneeFilter = null;
    public int $archivedCount = 0;

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.kanban-placeholder');
    }

    #[Computed]
    public function staffMembers(): array
    {
        return User::getCachedActiveOptions()
            ->except(auth()->id())
            ->map(fn($name, $id) => ['id' => $id, 'full_name' => $name])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function departmentOptions(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    public function loadKanbanBoard(): void
    {
        unset($this->kanbanBoard);
    }

    #[Computed]
    public function kanbanBoard(): array
    {
        $empty = ['todo' => [], 'in-progress' => [], 'pending' => [], 'done' => []];

        if (!$this->activeProjectId) {
            return ['tasks' => $empty, 'totalCount' => ['todo' => 0, 'in-progress' => 0, 'pending' => 0, 'done' => 0]];
        }

        $this->archivedCount = Task::where('project_id', $this->activeProjectId)
            ->where('status', TaskStatus::Done->value)
            ->whereNotNull('archived_at')
            ->count();

        $tasks = $this->kanbanScopedQuery()
            ->whereIn('status', self::KANBAN_COLUMNS)
            ->orderByRaw('rank IS NULL, rank')
            ->orderBy('created_at', 'desc')
            ->withCount('replies')
            ->get(self::KANBAN_COLUMNS_TO_SELECT);

        $tasks->load(self::KANBAN_RELATIONS);

        $collaboratorLookup = app(BoardCollaboratorResolver::class)->resolve($tasks);
        $lastTouchLookup = app(LastTouchResolver::class)->resolve($tasks->pluck('id')->all());

        $grouped = $empty;

        foreach ($tasks as $task) {
            if (array_key_exists($task->status, $grouped)) {
                $grouped[$task->status][] = $task;
            }
        }

        $kanbanTasks = [];
        $kanbanTotalCount = [];

        foreach (self::KANBAN_COLUMNS as $column) {
            $kanbanTotalCount[$column] = count($grouped[$column]);

            $kanbanTasks[$column] = collect($grouped[$column])->map(function (Task $task) use ($collaboratorLookup, $lastTouchLookup) {
                $data = $task->toArray();

                if ($data['detail'] !== null) {
                    $data['detail']['collaborator_users'] = collect($task->detail->collaborators ?? [])
                        ->map(fn($id) => $collaboratorLookup[$id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                }

                $data['last_touched'] = $lastTouchLookup[$task->id] ?? null;

                return $data;
            })->all();
        }

        return ['tasks' => $kanbanTasks, 'totalCount' => $kanbanTotalCount];
    }

    private function kanbanScopedQuery(): Builder
    {
        return Task::query()
            ->where('project_id', $this->activeProjectId)
            ->whereNull('archived_at')
            ->when($this->kanbanSearch !== '', fn(Builder $q) => $q->where(fn(Builder $sub) => $sub
                ->where('title', 'like', "%{$this->kanbanSearch}%")
                ->orWhere('description', 'like', "%{$this->kanbanSearch}%")
            ))
            ->when($this->kanbanDeadlineFilter !== '', fn(Builder $q) => $q
                ->whereNotNull('deadline')
                ->where('status', '!=', TaskStatus::Done->value)
                ->tap($this->kanbanDeadlineFilterScope()))
            ->when($this->kanbanPriorityFilter !== '', fn(Builder $q) => $q->where('priority', $this->kanbanPriorityFilter))
            ->when(!empty($this->kanbanLabelFilter), function (Builder $q) {
                foreach ($this->kanbanLabelFilter as $label) {
                    $q->whereJsonContains('labels', $label);
                }
            })
            ->when(
                $this->kanbanResponsibleFilter || $this->kanbanDepartmentFilter || $this->kanbanSchemeFilter !== '' || $this->kanbanUnitFilter || $this->kanbanSectionFilter,
                fn(Builder $q) => $q->whereHas('detail', fn(Builder $dq) => $dq
                    ->when($this->kanbanResponsibleFilter, fn(Builder $q2) => $q2->where('responsible_user_id', $this->kanbanResponsibleFilter))
                    ->when($this->kanbanDepartmentFilter, fn(Builder $q2) => $q2->where('department_id', $this->kanbanDepartmentFilter))
                    ->when($this->kanbanSchemeFilter !== '', fn(Builder $q2) => $q2->where('scheme', $this->kanbanSchemeFilter))
                    ->when($this->kanbanUnitFilter, fn(Builder $q2) => $q2->where('unit', $this->kanbanUnitFilter))
                    ->when($this->kanbanSectionFilter, fn(Builder $q2) => $q2->where('section', $this->kanbanSectionFilter))
                )
            )
            ->when($this->kanbanAssigneeFilter, fn(Builder $q) => $q->where('assigned_to', $this->kanbanAssigneeFilter));
    }

    private function kanbanDeadlineFilterScope(): callable
    {
        return match ($this->kanbanDeadlineFilter) {
            'overdue' => fn(Builder $q) => $q->where('deadline', '<', now()->startOfDay()),
            'today' => fn(Builder $q) => $q->whereDate('deadline', now()->toDateString()),
            'week' => fn(Builder $q) => $q->whereBetween('deadline', [
                now()->startOfWeek(Carbon::SATURDAY),
                now()->startOfWeek(Carbon::SATURDAY)->addDays(6)->endOfDay(),
            ]),
            default => fn(Builder $q) => $q,
        };
    }

    #[Computed]
    public function kanbanDeadlineFilterCounts(): array
    {
        if (!$this->activeProjectId) {
            return ['overdue' => 0, 'today' => 0, 'week' => 0];
        }

        return app(ReportingService::class)->boardDeadlineCounts(
            Task::query()->where('project_id', $this->activeProjectId)
        );
    }

    #[Computed]
    public function kanbanActiveFilterCount(): int
    {
        return collect([
            $this->kanbanDeadlineFilter !== '',
            $this->kanbanPriorityFilter !== '',
            $this->kanbanLabelFilter !== [],
            $this->kanbanSchemeFilter !== '',
            $this->kanbanUnitFilter !== null,
            $this->kanbanSectionFilter !== null,
            $this->kanbanResponsibleFilter !== null,
            $this->kanbanDepartmentFilter !== null,
            $this->kanbanAssigneeFilter !== null,
        ])->filter()->count();
    }

    public function toggleKanbanMine(): void
    {
        $this->kanbanAssigneeFilter = (int) $this->kanbanAssigneeFilter === (int) auth()->id() ? null : (int) auth()->id();
        $this->loadKanbanBoard();
    }

    public function clearKanbanFilters(): void
    {
        $this->kanbanDeadlineFilter = '';
        $this->kanbanPriorityFilter = '';
        $this->kanbanLabelFilter = [];
        $this->kanbanSchemeFilter = '';
        $this->kanbanUnitFilter = null;
        $this->kanbanSectionFilter = null;
        $this->kanbanResponsibleFilter = null;
        $this->kanbanDepartmentFilter = null;
        $this->kanbanAssigneeFilter = null;

        $this->loadKanbanBoard();
    }

    #[Computed]
    public function kanbanLabelOptions(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        $projectId = $this->activeProjectId;
        $key = sprintf('kanban:label_options:%d:t%s', $projectId, ModelCacheVersion::version(Task::class));

        return Cache::flexible($key, [self::LABEL_OPTIONS_FRESH_SECONDS, self::LABEL_OPTIONS_STALE_SECONDS], fn() => Task::where('project_id', $projectId)
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all());
    }

    #[Computed]
    public function archivedTasks(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return Task::where('project_id', $this->activeProjectId)
            ->where('status', TaskStatus::Done->value)
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->get(['id', 'title', 'archived_at', 'user_id'])
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'archived_at' => toJalaliRelative($task->archived_at),
                'can_restore' => $task->user_id === auth()->id(),
            ])->all();
    }

    public function unarchiveTask(UnarchiveTaskAction $action, int $taskId): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->find($taskId);

        if (!$task || !$action->execute($taskId)) {
            return;
        }

        unset($this->archivedTasks);
        $this->loadKanbanBoard();
        $this->dispatch('toast', message: 'وظیفه از آرشیو خارج شد.', type: 'success');
    }

    private function projectTaskScope(): \Closure
    {
        $projectId = $this->activeProjectId;

        return fn(Builder $q) => $q->where('project_id', $projectId);
    }

    #[Computed]
    public function kanbanSchemeOptions(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return app(ReportingService::class)->boardSchemeOptions($this->projectTaskScope(), 'p' . $this->activeProjectId);
    }

    #[Computed]
    public function kanbanUnitOptions(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return app(ReportingService::class)->boardUnitOptions($this->projectTaskScope(), 'p' . $this->activeProjectId);
    }

    #[Computed]
    public function kanbanSectionOptions(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return app(ReportingService::class)->boardSectionOptions($this->projectTaskScope(), 'p' . $this->activeProjectId);
    }

    #[Computed]
    public function kanbanAssigneeOptions(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return app(ReportingService::class)->boardAssigneeOptions($this->projectTaskScope(), 'p' . $this->activeProjectId);
    }

    public function addKanbanLabelFilter(string $label): void
    {
        if (in_array($label, $this->kanbanLabelFilter, true)) {
            return;
        }

        $this->kanbanLabelFilter[] = $label;
        $this->loadKanbanBoard();
    }

    public function toggleKanbanLabelFilter(string $label): void
    {
        $this->kanbanLabelFilter = in_array($label, $this->kanbanLabelFilter, true)
            ? array_values(array_diff($this->kanbanLabelFilter, [$label]))
            : [...$this->kanbanLabelFilter, $label];

        $this->loadKanbanBoard();
    }

    public function updatedKanbanSearch(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanDeadlineFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanPriorityFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanLabelFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanSchemeFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanUnitFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanSectionFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanDepartmentFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanResponsibleFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function updatedKanbanAssigneeFilter(): void
    {
        $this->loadKanbanBoard();
    }

    public function cyclePriority(CyclePriorityAction $action, int $taskId): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->find($taskId);

        if (!$task) {
            return;
        }

        if ($action->execute($taskId)) {
            $this->loadKanbanBoard();
        }
    }

    public function approveTask(ApproveTaskAction $action, int $taskId): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->find($taskId);

        if (!$task) {
            return;
        }

        if ($action->execute($task, auth()->user())) {
            $this->loadKanbanBoard();
            $this->dispatch('toast', message: 'وظیفه تأیید شد.', type: 'success');
        }
    }

    public function reorderTask(ReorderTaskAction $action, int $taskId, ?int $beforeTaskId, ?string $targetStatus = null): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->with('detail:id,task_id,state')->find($taskId);

        if (!$task) return;

        if ($targetStatus === TaskStatus::Done->value && empty($task->detail?->state)) {
            $this->dispatch('toast', message: 'برای انتقال به «انجام‌شده» ابتدا تعیین تکلیف را مشخص کنید.', type: 'error');
            return;
        }

        $action->execute($task, $beforeTaskId, $targetStatus);
        $this->loadKanbanBoard();
    }

    public function updateTaskStatus(UpdateTaskStatusAction $action, int $taskId, string $newColumn): void
    {
        $task = Task::where('project_id', $this->activeProjectId)->with('detail:id,task_id,state')->find($taskId);

        if (!$task) return;

        if ($newColumn === TaskStatus::Done->value && empty($task->detail?->state)) {
            $this->dispatch('toast', message: 'برای انتقال به «انجام‌شده» ابتدا تعیین تکلیف را مشخص کنید.', type: 'error');
            return;
        }

        if ($action->execute($taskId, $newColumn)) {
            $this->loadKanbanBoard();
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.kanban', [
            'taskBoardPresenter' => new TaskBoardPresenter(),
            'dmsPresenter' => new DmsPresenter(),
        ]);
    }
}
