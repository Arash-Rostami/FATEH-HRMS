<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerThemeBootstrap();
        $this->registerCoreScripts();
        $this->registerComponentHooks();
        $this->registerTableDefaults();
    }

    private function registerComponentHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn (): string => view('components.dashboard.navbars.top.palette')->render()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_START,
            fn (): string => view('filament.resources.dashboard.utilities')->render()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => view('components.ui.decor.panel-ghost')->render()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => view('components.ui.loaders.screen-saver')->render()
                . view('components.ui.loaders.page-transition')->render()
        );
    }

    private function registerCoreScripts(): void
    {
        $scripts = [
            'resources/js/core/theme-manager.js',
            'resources/js/core/filament.js',
            'resources/js/core/topbar-autohide.js',
            'resources/js/core/nav-dock.js',
            'resources/js/components/alpine/stores/filament-menu.js',
        ];

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => implode('', array_map(
                fn ($script) => Blade::render("@vite('{$script}')"),
                $scripts
            ))
        );
    }

    private function registerTableDefaults(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(fn (): int => (int) (Auth::user()?->getPreference('records_per_page', 25) ?? 25))
                ->reorderableColumns();
        });
    }

    private function registerThemeBootstrap(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            function (): string {
                $user = Auth::user();

                $navDock = (bool) ($user?->getPreference('nav_dock', false));
                $topbarPinned = (bool) ($user?->getPreference('topbar_pinned', true) ?? true);

                $classes = array_filter([
                    $navDock ? 'nav-dock-bottom' : null,
                    $topbarPinned ? 'topbar-pinned' : null,
                ]);

                $script = '<script src="' . asset('js/mode-manager.js') . '"></script>';

                // localStorage lets topbar-autohide.js resync these classes on every wire:navigate — see that file
                $script .= '<script>'
                    . 'localStorage.setItem("nav-dock-bottom", ' . json_encode($navDock) . ');'
                    . 'localStorage.setItem("topbar-pinned", ' . json_encode($topbarPinned) . ');'
                    . 'document.documentElement.classList.add(...' . json_encode(array_values($classes)) . ')'
                    . '</script>';

                return $script;
            }
        );
    }
}
