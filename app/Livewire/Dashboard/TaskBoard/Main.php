<?php

namespace App\Livewire\Dashboard\TaskBoard;

use App\Models\Task;
use App\Models\User;
use App\Traits\TaskBoardState;
use App\Traits\TaskBoardValidation;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

class Main extends Component
{
    use TaskBoardState;
    use TaskBoardValidation;

    public function createTask()
    {
        $this->validate();

        $deadline = null;
        if ($this->deadlineYear && $this->deadlineMonth && $this->deadlineDay) {
            try {
                $farsiDate = sprintf('%s/%02d/%02d', $this->deadlineYear, $this->deadlineMonth, $this->deadlineDay);
                $deadline = CalendarUtils::createCarbonFromFormat('Y/m/d', $farsiDate);
            } catch (Exception $e) {
                $this->addError('deadline', 'تاریخ وارد شده معتبر نیست');
                return;
            }
        }

        Task::create([
            'title' => $this->newTitle,
            'description' => $this->newDescription,
            'status' => 'todo',
            'deadline' => $deadline,
            'user_id' => auth()->id(),
            'assigned_to' => $this->selectedAssignee ?: null,
        ]);

        $this->reset(['newTitle', 'newDescription', 'deadlineYear', 'deadlineMonth', 'deadlineDay', 'selectedAssignee']);
        $this->loadTasks();

        $this->isCreateModalOpen = false;
        $this->dispatch('task-created');
        $this->dispatch('toast', message: 'وظیفه با موفقیت اضافه شد.', type: 'success');
    }

    public function deleteTask($taskId)
    {
        $task = Task::find($taskId);

        if ($task && $task->can_delete) {
            $task->delete();
            $this->loadTasks();
            $this->dispatch('toast', message: 'وظیفه با موفقیت حذف شد.', type: 'success');
        }
    }

    public function editTask($taskId)
    {
        $task = Task::find($taskId);

        if ($task && ($task->can_change_status || $task->user_id === auth()->id())) {
            $this->editingTaskId = $taskId;
            $this->newTitle = $task->title;
            $this->newDescription = $task->description;

            if ($task->deadline) {
                $jDate = Jalalian::fromCarbon($task->deadline);
                $this->deadlineYear = $jDate->getYear();
                $this->deadlineMonth = $jDate->getMonth();
                $this->deadlineDay = $jDate->getDay();
            } else {
                $this->reset(['deadlineYear', 'deadlineMonth', 'deadlineDay']);
            }

            $this->selectedAssignee = $task->assigned_to;

            $this->isEditModalOpen = true;
        }
    }

    public function loadTasks()
    {
        $userId = auth()->id();

        foreach ($this->columns as $column) {
            $skip = ($this->page[$column] - 1) * $this->perPage;

            $query = Task::query()
                ->where('status', $column)
                ->when($this->activeTab === 'my-tasks', function ($q) use ($userId) {
                    $q->where(function ($sub) use ($userId) {
                        $sub->where('assigned_to', $userId)
                            ->orWhere(function ($sub2) use ($userId) {
                                $sub2->where('user_id', $userId)->whereNull('assigned_to');
                            });
                    });
                })
                ->when($this->activeTab === 'assigned-tasks', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                        ->whereNotNull('assigned_to')
                        ->where('assigned_to', '!=', $userId);
                });

            $this->totalCount[$column] = (clone $query)->count();

            $this->tasks[$column] = $query->orderBy('created_at', 'desc')
                ->skip($skip)
                ->take($this->perPage)
                ->with($this->relationsToLoad)
                ->get($this->columnsToSelect)
                ->toArray();
        }
    }

    public function mount()
    {
        $currentYear = Jalalian::now()->getYear();
        $this->years = range($currentYear, $currentYear + 3);

        $this->staffMembers = Cache::remember("staff_" . auth()->id(), 3600, fn() => collect(User::where('status', 'active')->where('id', '!=', auth()->id())->get(['id', 'name']))
            ->map(fn($user) => ['id' => $user->id, 'full_name' => $user->full_name])
            ->values()
            ->toArray()
        );

        $this->loadTasks();
    }

    public function nextPage(string $column)
    {
        $maxPage = (int)ceil($this->totalCount[$column] / $this->perPage);

        if ($this->page[$column] < $maxPage) {
            $this->page[$column]++;
            $this->loadTasks();
        }
    }

    public function openCreateModal()
    {
        $this->reset(['newTitle', 'newDescription', 'deadlineYear', 'deadlineMonth', 'deadlineDay', 'selectedAssignee']);
        $this->isCreateModalOpen = true;
    }

    public function prevPage(string $column)
    {
        if ($this->page[$column] > 1) {
            $this->page[$column]--;
            $this->loadTasks();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.taskboard.index')
            ->extends('layouts.app')
            ->section('content');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];
        $this->loadTasks();
    }

    public function undoAssignment($taskId)
    {
        $task = Task::find($taskId);

        if ($task && $task->is_delegator) {
            $task->update(['assigned_to' => null]);
            $this->activeTab = 'my-tasks';
            $this->page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];
            $this->loadTasks();
        }
    }

    public function updateTask()
    {
        $this->validate();

        $task = Task::find($this->editingTaskId);

        if (!$task) return;

        $deadline = null;
        if ($this->deadlineYear && $this->deadlineMonth && $this->deadlineDay) {
            try {
                $farsiDate = sprintf('%s/%02d/%02d', $this->deadlineYear, $this->deadlineMonth, $this->deadlineDay);
                $deadline = CalendarUtils::createCarbonFromFormat('Y/m/d', $farsiDate);
            } catch (Exception $e) {
                $this->addError('deadline', 'تاریخ وارد شده معتبر نیست');
                return;
            }
        }

        $task->update([
            'title' => $this->newTitle,
            'description' => $this->newDescription,
            'deadline' => $deadline,
            'assigned_to' => $this->selectedAssignee ?: null,
        ]);

        $this->reset(['editingTaskId', 'newTitle', 'newDescription', 'deadlineYear', 'deadlineMonth', 'deadlineDay', 'selectedAssignee']);
        $this->loadTasks();

        $this->isEditModalOpen = false;
        $this->dispatch('toast', message: 'وظیفه با موفقیت بروزرسانی شد.', type: 'success');
    }

    public function updateTaskStatus($taskId, $newColumn)
    {
        $task = Task::find($taskId);

        if ($task && $task->can_change_status) {
            $task->update(['status' => $newColumn]);
            $this->loadTasks();
        }
    }
}
