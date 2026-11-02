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
        $moduleChunk = $this->moduleChunk();

        $view->with([
            'moduleChunk' => $moduleChunk,
            'maxScope' => $this->maxScope($moduleChunk),
        ]);
    }

    private function moduleChunk(): ?string
    {
        return self::MODULE_CHUNKS[request()->route()?->getName()] ?? null;
    }

    private function maxScope(?string $moduleChunk): ?string
    {
        $tab = request()->query('tab');

        return match (request()->route()?->getName()) {
            'dashboard' => ['home' => 'hero', 'feed' => 'feed', 'calendar' => 'calendar'][$tab ?? 'home'] ?? null,
            'energy' => $tab === 'chart' ? 'energy' : null,
            default => $moduleChunk,
        };
    }
}