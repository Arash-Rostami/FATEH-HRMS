<?php

namespace App\Traits;

trait TaskBoardState
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

    public bool $isCreateModalOpen = false;
    public bool $isEditModalOpen = false;

    public array $columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'user_id', 'assigned_to'];
    public array $relationsToLoad = ['assignee:id,name', 'creator:id,name'];
    public array $columnConfig = [
        'todo' => [
            'title' => 'انجام نشده',
            'icon' => '🧾',
            'color' => 'primary',
            'lightGradient' => 'from-rose-500 to-pink-600',
            'darkGradient' => 'from-rose-700 to-pink-800',
        ],
        'in-progress' => [
            'title' => 'در حال انجام',
            'icon' => '⏳',
            'color' => 'secondary',
            'lightGradient' => 'from-amber-500 to-orange-600',
            'darkGradient' => 'from-amber-700 to-orange-800'
        ],
        'done' => [
            'title' => 'انجام شده',
            'icon' => '🎯',
            'color' => 'tertiary',
            'lightGradient' => 'from-emerald-500 to-green-600',
            'darkGradient' => 'from-emerald-700 to-green-800'
        ],
    ];
}
