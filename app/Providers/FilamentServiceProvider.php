<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerCoreScripts();
        $this->registerComponentHooks();
    }

    private function registerComponentHooks(): void
    {
        $hooks = [
            [PanelsRenderHook::GLOBAL_SEARCH_AFTER, 'components.dashboard.navbars.top.palette'],
            [PanelsRenderHook::BODY_END, 'components.ui.loaders.screen-saver'],
            [PanelsRenderHook::BODY_START, 'components.ui.decor.panel-ghost'],
            [PanelsRenderHook::BODY_END, 'components.ui.loaders.spinner'],
        ];

        foreach ($hooks as [$hook, $view]) {
            FilamentView::registerRenderHook(
                $hook,
                fn(): string => view($view)->render()
            );
        }
    }

    private function registerCoreScripts(): void
    {
        $scripts = [
            'resources/js/core/theme-manager.js',
            'resources/js/core/filament.js',
            'resources/js/components/alpine/stores/filament-menu.js',
        ];

        foreach ($scripts as $script) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn(): string => Blade::render(sprintf("@vite('%s')", $script))
            );
        }
    }
}
