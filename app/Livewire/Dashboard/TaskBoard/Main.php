<?php

namespace App\Livewire\Dashboard\Taskboard;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;

class Main extends Component
{
    public array $tasks = ['todo' => [], 'in-progress' => [], 'done' => []];
    public array $columns = ['todo', 'in-progress', 'done'];
    public array $totalCount = ['todo' => 0, 'in-progress' => 0, 'done' => 0];
    public array $page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];

    public array $months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    ];

    public string $activeTab = 'my-tasks';
    public $staffMembers = [];
    public $selectedAssignee = null;
    public $editingTaskId = null;

    public $newTitle = '';
    public $newDescription = '';
    public $deadlineYear = '';
    public $deadlineMonth = '';
    public $deadlineDay = '';

    public array $years = [];
    public int $perPage = 4;

    public array $columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'user_id', 'assigned_to'];
    public array $relationsToLoad = ['assignee:id,name', 'creator:id,name'];

    public array $columnConfig = [
        'todo' => [
            'title' => 'انجام نشده',
            'icon' => '🧾',
            'color' => 'rose',
            'lightGradient' => 'from-rose-500 to-pink-600',
            'darkGradient' => 'from-rose-700 to-pink-800',
        ],
        'in-progress' => [
            'title' => 'در حال انجام',
            'icon' => '⏳',
            'color' => 'amber',
            'lightGradient' => 'from-amber-500 to-orange-600',
            'darkGradient' => 'from-amber-700 to-orange-800'
        ],
        'done' => [
            'title' => 'تکمیل شده',
            'icon' => '🎯',
            'color' => 'emerald',
            'lightGradient' => 'from-emerald-500 to-green-600',
            'darkGradient' => 'from-emerald-700 to-green-800'
        ]
    ];

    protected $rules = [
        'newTitle' => 'required|string|max:255',
        'newDescription' => 'nullable|string',
        'deadlineYear' => 'nullable|integer',
        'deadlineMonth' => 'nullable|integer|min:1|max:12',
        'deadlineDay' => 'nullable|integer|min:1|max:31',
    ];

    protected $messages = [
        'newTitle.required' => 'فیلد عنوان الزامی است.',
        'newTitle.string' => 'عنوان باید یک متن باشد.',
        'newTitle.max' => 'عنوان نباید بیش از :max کاراکتر باشد.',
        'newDescription.string' => 'توضیحات باید یک متن باشد.',
        'deadlineYear.integer' => 'سال سررسید باید یک عدد صحیح باشد.',
        'deadlineMonth.integer' => 'ماه سررسید باید یک عدد صحیح باشد.',
        'deadlineMonth.min' => 'ماه باید بین 1 تا 12 باشد.',
        'deadlineMonth.max' => 'ماه باید بین 1 تا 12 باشد.',
        'deadlineDay.integer' => 'روز سررسید باید یک عدد صحیح باشد.',
        'deadlineDay.min' => 'روز باید بین 1 تا 31 باشد.',
        'deadlineDay.max' => 'روز باید بین 1 تا 31 باشد.',
    ];

    protected $validationAttributes = [
        'newTitle' => 'عنوان',
        'newDescription' => 'توضیحات',
        'deadlineYear' => 'سال سررسید',
        'deadlineMonth' => 'ماه سررسید',
        'deadlineDay' => 'روز سررسید',
    ];

    public function mount()
    {
        $currentYear = Jalalian::now()->getYear();
        $this->years = range($currentYear, $currentYear + 3);

        $this->staffMembers = Cache::remember("staff_" . auth()->id(), 3600, fn() =>
        collect(User::where('status', 'active')->where('id', '!=', auth()->id())->get(['id', 'name']))
            ->map(fn($user) => ['id' => $user->id, 'full_name' => $user->full_name])
            ->values()
            ->toArray()
        );

        $this->loadTasks();
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

    public function createTask()
    {
        $this->validate();

        $deadline = null;
        if ($this->deadlineYear && $this->deadlineMonth && $this->deadlineDay) {
            try {
                $farsiDate = sprintf('%s/%02d/%02d', $this->deadlineYear, $this->deadlineMonth, $this->deadlineDay);
                $deadline = CalendarUtils::createCarbonFromFormat('Y/m/d', $farsiDate);
            } catch (\Exception $e) {
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

        $this->dispatch('task-created');
    }

    public function deleteTask($taskId)
    {
        $task = Task::find($taskId);

        if ($task && $task->can_delete) {
            $task->delete();
            $this->loadTasks();
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

            $this->dispatch('open-modal', 'edit-task-modal');
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
            } catch (\Exception $e) {
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

        $this->dispatch('task-updated');
    }

    public function nextPage(string $column)
    {
        $maxPage = (int)ceil($this->totalCount[$column] / $this->perPage);

        if ($this->page[$column] < $maxPage) {
            $this->page[$column]++;
            $this->loadTasks();
        }
    }

    public function prevPage(string $column)
    {
        if ($this->page[$column] > 1) {
            $this->page[$column]--;
            $this->loadTasks();
        }
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

    public function updateTaskStatus($taskId, $newColumn)
    {
        $task = Task::find($taskId);

        if ($task && $task->can_change_status) {
            $task->update(['status' => $newColumn]);
            $this->loadTasks();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.taskboard.index')
            ->extends('layouts.app')
            ->section('content');
    }
}
