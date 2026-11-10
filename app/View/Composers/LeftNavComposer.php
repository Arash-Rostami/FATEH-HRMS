<?php

namespace App\View\Composers;

use Illuminate\View\View;

class LeftNavComposer
{
    private const ITEMS = [
        ['path' => '/tasks', 'module' => 'taskboard', 'id' => 'tasks', 'icon' => 'dashboard', 'label' => 'برد وظایف', 'delay' => 'animate-delay-150'],
        ['path' => '/projects', 'module' => 'project', 'id' => 'projects', 'icon' => 'workspaces', 'label' => 'پروژه‌ها', 'delay' => 'animate-delay-200'],
        ['path' => '/dms', 'module' => 'dms', 'id' => 'dms', 'icon' => 'folder_open', 'label' => 'مدیریت اسناد', 'delay' => 'animate-delay-300'],
        ['path' => '/ths', 'module' => 'ths', 'id' => 'ths', 'icon' => 'support_agent', 'label' => 'تیکتینگ', 'delay' => 'animate-delay-500'],
    ];

    public function compose(View $view): void
    {
        $view->with('navItems', array_map(
            fn (array $item) => ['href' => url($item['path'])] + array_diff_key($item, ['path' => true]),
            self::ITEMS,
        ));
    }
}
