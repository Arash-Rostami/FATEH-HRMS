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
                . view('components.ui.loaders.spinner')->render()
        );
    }

    private function registerCoreScripts(): void
    {
        $scripts = [
            'resources/js/core/theme-manager.js',
            'resources/js/core/filament.js',
            'resources/js/core/topbar-autohide.js',
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
                ->defaultPaginationPageOption(fn (): int => (int) (Auth::user()?->getPreference('records_per_page', 25) ?? 25));
        });
    }

    private function registerThemeBootstrap(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            function (): string {
                $user = Auth::user();

                $classes = array_filter([
                    $user?->getPreference('nav_dock', false) ? 'nav-dock-bottom' : null,
                    ($user?->getPreference('topbar_pinned', true) ?? true) ? 'topbar-pinned' : null,
                ]);

                $script = '<script src="' . asset('js/mode-manager.js') . '"></script>';

                if ($classes) {
                    $script .= '<script>document.documentElement.classList.add(...' . json_encode(array_values($classes)) . ')</script>';
                }

                return $script;
            }
        );
    }
}
