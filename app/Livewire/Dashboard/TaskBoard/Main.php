<?php

namespace App\Livewire\Dashboard\TaskBoard;

use App\Livewire\Dashboard\TaskBoard\Actions\CreateTaskAction;
use App\Livewire\Dashboard\TaskBoard\Actions\UpdateTaskAction;
use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Models\Task;
use App\Models\User;
use App\Traits\FocusOnRecord;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Main extends Component
{
    use FocusOnRecord;

    public TaskForm $form;
    public array $tasks = ['todo' => [], 'in-progress' => [], 'done' => []];
    public array $totalCount = ['todo' => 0, 'in-progress' => 0, 'done' => 0];
    public array $page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];
    public string $activeTab = 'my-tasks';
    public int $perPage = 4;
    public bool $isCreateModalOpen = false;
    public bool $isEditModalOpen = false;
    public ?int $editingTaskId = null;
    public array $staffMembers = [];
    public array $columns = ['todo', 'in-progress', 'done'];
    public array $columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'user_id', 'assigned_to'];
    public array $relationsToLoad = ['assignee:id,name', 'creator:id,name'];

    public function assignTask(int $taskId, ?int $userId): void
    {
        $task = Task::find($taskId);

        if (!$task || !$task->can_change_status) {
            return;
        }

        $task->update(['assigned_to' => $userId]);

        if ($userId && $userId !== auth()->id()) {
            $assignee = User::find($userId);
            if ($assignee) {
                Notification::make()
                    ->title('وظیفه جدید به شما محول شد')
                    ->body("وظیفه «{$task->title}» توسط " . auth()->user()->name . " به شما ارجاع شد.")
                    ->success()
                    ->sendToDatabase($assignee);
            }
            $this->switchTab('assigned-tasks');
        } else {
            $this->loadTasks();
        }

        $this->dispatch('toast', message: 'وظیفه با موفقیت ارجاع داده شد.', type: 'success');
    }

    public function createTask(CreateTaskAction $action): void
    {
        try {
            $action->execute($this->form);
        } catch (Exception) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        }

        $this->form->reset();
        $this->loadTasks();
        $this->isCreateModalOpen = false;
        $this->dispatch('task-created');
        $this->dispatch('toast', message: 'وظیفه با موفقیت اضافه شد.', type: 'success');
    }

    public function deleteTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task?->can_delete) {
            $task->delete();
            $this->loadTasks();
            $this->dispatch('toast', message: 'وظیفه با موفقیت حذف شد.', type: 'success');
        }
    }

    public function editTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$task || (!$task->can_change_status && $task->user_id !== auth()->id())) return;

        $this->editingTaskId = $taskId;
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

        $this->isEditModalOpen = true;
    }

    public function focusRecord(int $id): void
    {
        $userId = auth()->id();

        $owned = Task::whereKey($id)
            ->where(fn(Builder $query) => $query->where('user_id', $userId)->orWhere('assigned_to', $userId)
            )->exists();

        if ($owned) {
            $this->editTask($id);
        }
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
            );

        $counts = (clone $baseQuery)
            ->whereIn('status', $this->columns)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        foreach ($this->columns as $column) {
            $this->totalCount[$column] = $counts->get($column, 0);

            $this->tasks[$column] = (clone $baseQuery)
                ->where('status', $column)
                ->orderBy('created_at', 'desc')
                ->skip(($this->page[$column] - 1) * $this->perPage)
                ->take($this->perPage)
                ->with($this->relationsToLoad)
                ->get($this->columnsToSelect)
                ->toArray();
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
        $this->isCreateModalOpen = true;
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
        return view('livewire.dashboard.taskboard', ['presenter' => new TaskBoardPresenter()])
            ->extends('layouts.app')
            ->section('content');
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];
        $this->loadTasks();
    }

    public function undoAssignment(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task?->is_delegator) {
            $task->update(['assigned_to' => null]);
            $this->switchTab('my-tasks');
        }
    }

    public function updateTask(UpdateTaskAction $action): void
    {
        $task = Task::find($this->editingTaskId);
        if (!$task) return;

        try {
            $action->execute($task, $this->form);
        } catch (Exception) {
            $this->addError('form.deadline', 'تاریخ وارد شده معتبر نیست');
            return;
        }

        $this->form->reset();
        $this->editingTaskId = null;
        $this->isEditModalOpen = false;
        $this->loadTasks();
        $this->dispatch('toast', message: 'وظیفه با موفقیت بروزرسانی شد.', type: 'success');
    }

    public function updateTaskStatus(int $taskId, string $newColumn): void
    {
        $task = Task::find($taskId);

        if ($task?->can_change_status) {
            $task->update(['status' => $newColumn]);
            $this->loadTasks();
        }
    }

    protected function recordFocusType(): string
    {
        return 'task';
    }
}
