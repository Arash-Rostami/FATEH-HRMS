<?php

namespace App\Livewire\Dashboard\TaskBoard;

use App\Livewire\Dashboard\TaskBoard\Actions\AssignTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkAssignTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkDeleteTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\BulkMoveTasksAction;
use App\Livewire\Dashboard\TaskBoard\Actions\CreateTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\DeleteTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UndoTaskAssignmentAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UpdateTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UpdateTaskStatusAction;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Traits\FocusOnRecord;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Main extends Component
{
    use FocusOnRecord;
    use WithFileUploads;

    public TaskForm $form;
    public array $tasks = ['todo' => [], 'in-progress' => [], 'pending' => [], 'done' => []];
    public array $totalCount = ['todo' => 0, 'in-progress' => 0, 'pending' => 0, 'done' => 0];
    public array $page = ['todo' => 1, 'in-progress' => 1, 'pending' => 1, 'done' => 1];
    public string $activeTab = 'my-tasks';
    public int $perPage = 4;

    #[Locked]
    public ?int $editingTaskId = null;
    public array $staffMembers = [];
    public array $departmentOptions = [];
    public array $columns = ['todo', 'in-progress', 'pending', 'done'];
    public array $columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'user_id', 'assigned_to'];
    public array $relationsToLoad = ['assignee:id,name', 'creator:id,name'];
    public string $search = '';
    public bool $selectionMode = false;

    public bool $isModalOpen = false;
    public bool $isEditMode = false;
    public bool $isReadOnly = false;

    public array $selectedTasks = [];
    public string $density = 'comfortable';

    public function assignTask(AssignTaskAction $action, int $taskId, ?int $userId): void
    {
        $task = $action->execute($taskId, $userId);

        if (!$task) {
            return;
        }

        if ($userId && $userId !== auth()->id()) {
            $this->switchTab('assigned-tasks');
        } else {
            $this->loadTasks();
        }

        $this->dispatch('toast', message: 'وظیفه با موفقیت ارجاع داده شد.', type: 'success');
    }

    #[Computed]
    public function selectedDepartment(): ?Department
    {
        return Department::getCachedModels()->get($this->form->departmentId);
    }

    #[Computed]
    public function availableSections(): array
    {
        return $this->selectedDepartment?->sectionsOptions() ?? [];
    }

    #[Computed]
    public function availableUnits(): array
    {
        return $this->selectedDepartment?->unitsOptions() ?? [];
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
        $action->execute($this->selectedTasks, $status);
        $this->selectedTasks = [];
        $this->loadTasks();
        $this->dispatch('toast', message: 'وظایف انتخاب‌شده با موفقیت منتقل شدند.', type: 'success');
    }

    public function createTask(CreateTaskAction $action): void
    {
        try {
            $action->execute($this->form);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        } catch (Exception $e) {
            report($e);
            $this->addError('form', 'عملیات با خطا مواجه شد؛ دوباره تلاش کنید.');
            return;
        }

        $this->form->reset();
        $this->loadTasks();
        $this->isModalOpen = false;
        $this->dispatch('task-created');
        $this->dispatch('toast', message: 'وظیفه با موفقیت اضافه شد.', type: 'success');
    }

    public function deleteTask(DeleteTaskAction $action, int $taskId): void
    {
        if (!$action->execute($taskId)) {
            return;
        }

        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه با موفقیت حذف شد.', type: 'success');
    }

    public function editTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status) return;

        $this->editingTaskId = $taskId;
        $this->populateFormFromTask($task);

        $this->isEditMode = true;
        $this->isReadOnly = false;
        $this->isModalOpen = true;
    }

    public function viewTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->is_delegator) return;

        $this->editingTaskId = $taskId;
        $this->populateFormFromTask($task);

        $this->isEditMode = false;
        $this->isReadOnly = true;
        $this->isModalOpen = true;
    }

    public function focusRecord(int $id): void
    {
        $userId = auth()->id();

        $owned = Task::whereKey($id)
            ->where(fn(Builder $query) => $query->where('user_id', $userId)->orWhere('assigned_to', $userId)
            )->exists();

        if (!$owned) return;

        $this->editTask($id);

        if (!$this->isModalOpen) {
            $this->viewTask($id);
        }
    }

    private function populateFormFromTask(Task $task): void
    {
        $this->form->newTitle = $task->title;
        $this->form->newDescription = $task->description;
        $this->form->selectedAssignee = $task->assigned_to;

        if ($task->deadline) {
            $jDate = Jalalian::fromCarbon($task->deadline);
            $this->form->deadlineYear = $jDate->getYear();
            $this->form->deadlineMonth = $jDate->getMonth();
            $this->form->deadlineDay = $jDate->getDay();
        } else {
            $this->form->reset(['deadlineYear', 'deadlineMonth', 'deadlineDay']);
        }

        $detail = $task->detail;
        $this->form->departmentId = $detail?->department_id;
        $this->form->unit = $detail?->unit;
        $this->form->section = $detail?->section;
        $this->form->project = $detail?->project;
        $this->form->scheme = $detail?->scheme;
        $this->form->actionSourceDomain = $detail?->action_source_domain;
        $this->form->actionSource = $detail?->action_source;
        $this->form->collaborators = $detail?->collaborators ?? [];
        $this->form->responsibleUserId = $detail?->responsible_user_id;
        $this->form->state = $detail?->state;
        $this->form->attachments = [];
        $this->form->existingAttachments = $detail?->attachments ?? [];
    }

    public function loadTasks(): void
    {
        $userId = auth()->id();

        $baseQuery = Task::query()
            ->when($this->activeTab === 'my-tasks', fn($q) => $q->where(fn($sub) => $sub
                ->where('assigned_to', $userId)
                ->orWhere(fn($sub2) => $sub2->where('user_id', $userId)->whereNull('assigned_to'))
            ))
            ->when($this->activeTab === 'assigned-tasks', fn($q) => $q
                ->where('user_id', $userId)
                ->whereNotNull('assigned_to')
                ->where('assigned_to', '!=', $userId)
            )
            ->when($this->search, fn($q) => $q->where(fn($sub) => $sub
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            ));

        $counts = (clone $baseQuery)
            ->whereIn('status', $this->columns)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        $unionQuery = null;

        foreach ($this->columns as $column) {
            $this->totalCount[$column] = $counts->get($column, 0);

            $columnQuery = (clone $baseQuery)
                ->where('status', $column)
                ->orderBy('created_at', 'desc')
                ->skip(($this->page[$column] - 1) * $this->perPage)
                ->take($this->perPage)
                ->select($this->columnsToSelect);

            if ($unionQuery === null) {
                $unionQuery = $columnQuery;
            } else {
                $unionQuery->unionAll($columnQuery);
            }
        }

        $allTasks = $unionQuery ? $unionQuery->with($this->relationsToLoad)->get() : collect();

        foreach ($this->columns as $column) {
            $this->tasks[$column] = $allTasks->where('status', $column)->values()->toArray();
        }
    }

    public function mount(): void
    {
        $authId = auth()->id();
        $this->staffMembers = User::getCachedActiveOptions()
            ->except($authId)
            ->map(fn($name, $id) => ['id' => $id, 'full_name' => $name])
            ->values()
            ->toArray();

        $this->departmentOptions = Department::getCachedOptions()->toArray();

        $this->density = auth()->user()?->getPreference('taskboard_density', 'comfortable') ?? 'comfortable';

        $this->loadTasks();
    }

    public function nextPage(string $column): void
    {
        if ($this->page[$column] < ceil($this->totalCount[$column] / $this->perPage)) {
            $this->page[$column]++;
            $this->loadTasks();
        }
    }

    public function openCreateModal(): void
    {
        $this->form->reset();
        $this->isEditMode = false;
        $this->isReadOnly = false;
        $this->isModalOpen = true;
    }

    public function prevPage(string $column): void
    {
        if ($this->page[$column] > 1) {
            $this->page[$column]--;
            $this->loadTasks();
        }
    }

    public function removeAttachment(int $index): void
    {
        unset($this->form->attachments[$index]);
        $this->form->attachments = array_values($this->form->attachments);
    }

    public function removeExistingAttachment(int $index): void
    {
        unset($this->form->existingAttachments[$index]);
        $this->form->existingAttachments = array_values($this->form->existingAttachments);
    }

    public function render()
    {
        return view('livewire.dashboard.taskboard', ['presenter' => new TaskBoardPresenter()])
            ->extends('layouts.app')
            ->section('content');
    }

    public function setDensity(string $density): void
    {
        if (!in_array($density, ['comfortable', 'compact'], true)) {
            return;
        }

        $this->density = $density;

        $user = auth()->user();
        $user->setExtraValue('preferences.taskboard_density', $density);
        $user->save();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->page = ['todo' => 1, 'in-progress' => 1, 'pending' => 1, 'done' => 1];
        $this->selectedTasks = [];
        $this->loadTasks();
    }

    public function toggleSelectionMode(): void
    {
        $this->selectionMode = !$this->selectionMode;
        $this->selectedTasks = [];
    }

    public function toggleTaskSelection(int $taskId): void
    {
        if (in_array($taskId, $this->selectedTasks, true)) {
            $this->selectedTasks = array_values(array_diff($this->selectedTasks, [$taskId]));
        } else {
            $this->selectedTasks[] = $taskId;
        }
    }

    public function undoAssignment(UndoTaskAssignmentAction $action, int $taskId): void
    {
        if ($action->execute($taskId)) {
            $this->switchTab('my-tasks');
        }
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form->unit = null;
        $this->form->section = null;
    }

    public function updatedSearch(): void
    {
        $this->page = ['todo' => 1, 'in-progress' => 1, 'pending' => 1, 'done' => 1];
        $this->loadTasks();
    }

    public function updateTask(UpdateTaskAction $action): void
    {
        $task = Task::find($this->editingTaskId);
        if (!$task) return;

        try {
            $action->execute($task, $this->form);
        } catch (ValidationException|HttpException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        } catch (Exception $e) {
            report($e);
            $this->addError('form', 'عملیات با خطا مواجه شد؛ دوباره تلاش کنید.');
            return;
        }

        $this->form->reset();
        $this->editingTaskId = null;
        $this->isModalOpen = false;
        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه با موفقیت بروزرسانی شد.', type: 'success');
    }

    public function updateTaskStatus(UpdateTaskStatusAction $action, int $taskId, string $newColumn): void
    {
        if ($action->execute($taskId, $newColumn)) {
            $this->loadTasks();
        }
    }

    protected function recordFocusType(): string
    {
        return 'task';
    }
}
