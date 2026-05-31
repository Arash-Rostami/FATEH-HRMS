<?php

namespace App\Livewire\Dashboard\TaskBoard\Presentation;

use Morilog\Jalali\Jalalian;

class TaskBoardPresenter
{
    public function columnConfig(): array
    {
        return [
            'todo'        => ['title' => __('resources/task/strings.status.todo') ?? 'انجام نشده',   'icon' => '🧾', 'color' => 'primary',  'lightGradient' => 'from-rose-500 to-pink-600',    'darkGradient' => 'from-rose-700 to-pink-800'],
            'in-progress' => ['title' => __('resources/task/strings.status.in_progress') ?? 'در حال انجام', 'icon' => '⏳', 'color' => 'secondary', 'lightGradient' => 'from-amber-500 to-orange-600',  'darkGradient' => 'from-amber-700 to-orange-800'],
            'delegated'   => ['title' => __('resources/task/strings.status.delegated') ?? 'تفویض‌شده',    'icon' => '🤝', 'color' => 'info', 'lightGradient' => 'from-blue-500 to-indigo-600',  'darkGradient' => 'from-blue-700 to-indigo-800'],
            'done'        => ['title' => __('resources/task/strings.status.done') ?? 'انجام شده',    'icon' => '🎯', 'color' => 'tertiary',  'lightGradient' => 'from-emerald-500 to-green-600', 'darkGradient' => 'from-emerald-700 to-green-800'],
        ];
    }

    public function months(): array
    {
        return [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر',     5 => 'مرداد',    6 => 'شهریور',
            7 => 'مهر',     8 => 'آبان',     9 => 'آذر',
            10 => 'دی',     11 => 'بهمن',    12 => 'اسفند',
        ];
    }

    public function years(): array
    {
        $current = Jalalian::now()->getYear();
        return range($current, $current + 3);
    }
}
