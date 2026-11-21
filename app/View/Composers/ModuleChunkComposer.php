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
        'ai-report' => 'ai-report', 'ai-report.shared' => 'ai-report',
        'correspondence' => 'correspondence',
        'knowledge' => 'knowledge',
    ];

    public function compose(View $view): void
    {
        $moduleChunk = $this->moduleChunk();
        $embed = request()->boolean('embed');

        $view->with([
            'moduleChunk' => $moduleChunk,
            'maxScope' => $this->maxScope($moduleChunk),
            'embed' => $embed,
            'chrome' => ! $embed && ! $view->getFactory()->hasSection('minimal_layout'),
            'backdrop' => $this->backdrop(),
            'viteEntries' => $this->viteEntries($moduleChunk),
        ]);
    }

    private function backdrop(): array
    {
        return array_merge(config('app.backdrop'), [
            'images' => array_map('asset', config('app.backdrop.images')),
        ]);
    }

    private function viteEntries(?string $moduleChunk): array
    {
        $entries = ['resources/css/app.css', 'resources/js/app.js'];

        if ($moduleChunk) {
            $entries[] = "resources/js/components/alpine/modules/{$moduleChunk}.js";
        }

        return $entries;
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
            'knowledge' => $tab === 'media' ? 'media' : null,
            default => $moduleChunk,
        };
    }
}