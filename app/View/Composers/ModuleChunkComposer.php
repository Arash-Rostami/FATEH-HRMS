<?php

namespace App\View\Composers;

use Illuminate\View\View;

class ModuleChunkComposer
{
    private const MODULE_CHUNKS = [
        'dms' => 'dms', 'ths' => 'ths', 'tasks' => 'taskboard',
        'projects' => 'project', 'channels' => 'channel', 'contact' => 'contact',
        'reservation' => 'reservation', 'profile' => 'profile',
        'analytics' => 'analytics', 'energy' => 'energy',
        'tasksheet' => 'tasksheet', 'tasksheet.shared' => 'tasksheet',
    ];

    public function compose(View $view): void
    {
        $route = request()->route()?->getName();
        $tab = request()->query('tab');

        $moduleChunk = self::MODULE_CHUNKS[$route] ?? null;

        $maxScope = match ($route) {
            'dashboard' => ['home' => 'hero', 'feed' => 'feed', 'calendar' => 'calendar'][$tab ?? 'home'] ?? null,
            'energy' => $tab === 'chart' ? 'energy' : null,
            default => $moduleChunk,
        };

        $view->with('moduleChunk', $moduleChunk)->with('maxScope', $maxScope);
    }
}
