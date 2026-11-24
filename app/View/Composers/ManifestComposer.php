<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ManifestComposer
{
    private const PREFERRED = ['contact', 'channel', 'reminder', 'taskboard'];

    private const LIMIT = 4;

    public function compose(View $view): void
    {
        $view->with(['shortcuts' => $this->shortcuts(), 'icons' => $this->icons()]);
    }

    private function icons(): array
    {
        $icons = [];

        foreach (['any' => 'icon-', 'maskable' => 'icon-maskable-'] as $purpose => $prefix) {
            foreach ([192, 512] as $size) {
                if ($path = tenantAsset(config('app.tenant'), 'img', "{$prefix}{$size}")) {
                    $icons[] = ['src' => asset($path), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => $purpose];
                }
            }
        }

        return $icons;
    }

    private function shortcuts(): array
    {
        $rank = array_flip(self::PREFERRED);

        return collect(config('modules'))
            ->reject(fn (array $module) => tenantOff($module['id']))
            ->sortBy(fn (array $module) => $rank[$module['id']] ?? count($rank))
            ->map(fn (array $module) => ['name' => trim($module['title']), 'short_name' => trim($module['title']), 'url' => $this->url($module['nav'] ?? [])])
            ->filter(fn (array $shortcut) => $shortcut['url'] !== null)
            ->take(self::LIMIT)
            ->values()
            ->all();
    }

    private function url(array $nav): ?string
    {
        return match ($nav['type'] ?? null) {
            'tab' => route('dashboard', ['tab' => $nav['target']], false),
            'route' => Route::has($nav['target']) ? route($nav['target'], [], false) : null,
            default => null,
        };
    }
}
