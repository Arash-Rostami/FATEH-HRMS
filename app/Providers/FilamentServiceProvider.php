<?php

namespace App\Providers;

use App\Notifications\PasswordResetNotification;
use App\Traits\FilamentViewActionDefaults;
use Filament\Actions\View\ActionsRenderHook;
use Filament\Actions\ViewAction;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Js;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    use FilamentViewActionDefaults;

    private const SCRIPTS = [
        'resources/js/core/theme-manager.js',
        'resources/js/core/tooltip.js',
        'resources/js/core/filament.js',
        'resources/js/core/topbar-autohide.js',
        'resources/js/core/nav-dock.js',
        'resources/js/core/auto-close.js',
        'resources/js/core/table-density.js',
        'resources/js/core/recents.js',
        'resources/js/components/alpine/stores/filament-menu.js',
    ];

    public function boot(): void
    {
        $this->registerThemeBootstrap();
        $this->registerCoreScripts();
        $this->registerComponentHooks();
        $this->registerTableDefaults();
        $this->registerPasswordResetMail();
    }

    private function registerPasswordResetMail(): void
    {
        $this->app->bind(
            FilamentResetPassword::class,
            fn ($app, array $parameters) => new PasswordResetNotification($parameters['token']),
        );
    }

    private function registerComponentHooks(): void
    {
        $views = [
            PanelsRenderHook::GLOBAL_SEARCH_START => ['filament.resources.dashboard.utilities'],
            PanelsRenderHook::BODY_START => ['components.ui.decor.panel-ghost'],
            PanelsRenderHook::BODY_END => [
                'components.ui.loaders.screen-saver',
                'components.ui.loaders.page-transition',
            ],
        ];

        foreach ($views as $hook => $names) {
            FilamentView::registerRenderHook(
                $hook,
                fn (): string => collect($names)->map(fn (string $name) => view($name)->render())->implode(''),
            );
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_START,
            fn (): string => view('filament.partials.dock-arrow', ['edge' => 'start'])->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => view('filament.partials.dock-arrow', ['edge' => 'end'])->render()
                . view('filament.partials.dock-min')->render(),
        );

        FilamentView::registerRenderHook(
            ActionsRenderHook::MODAL_CUSTOM_CONTENT_BEFORE,
            fn (array $data): string => static::quickActionIcons($data['action'] ?? null),
        );

        ViewAction::configureUsing(
            fn (ViewAction $action) => $action->extraModalFooterActions(
                fn (?Table $table): array => static::viewModalShortcuts($table)
            )
        );
    }

    private function registerCoreScripts(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): string => Blade::render('@vite($scripts)', ['scripts' => self::SCRIPTS]),
        );
    }

    private function registerTableDefaults(): void
    {
        Table::configureUsing(function (Table $table): void {
            $persist = fn (): bool => (bool) Auth::user()?->getPreference('persist_table_state', false);

            $table
                ->persistFiltersInSession($persist)
                ->persistSearchInSession($persist)
                ->persistSortInSession($persist)
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(fn (): int => (int) (Auth::user()?->getPreference('records_per_page', 25) ?? 25))
                ->reorderableColumns()
                ->deferLoading();
        });
    }

    private function registerThemeBootstrap(): void
    {
        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_START, function (): string {
            $user = Auth::user();

            $theme = Js::from(config('app.theme_palette'));
            $modeManager = asset('js/mode-manager.js');
            $dock = Js::from((bool) $user?->getPreference('nav_dock', false));
            $pinned = Js::from((bool) ($user?->getPreference('topbar_pinned', true) ?? true));

            return <<<HTML
            <script>window.__defaultTheme = {$theme};</script>
            <script src="{$modeManager}"></script>
            <script>
                (() => {
                    const root = document.documentElement;
                    localStorage.setItem('nav-dock-bottom', {$dock});
                    localStorage.setItem('topbar-pinned', {$pinned});
                    root.classList.toggle('nav-dock-bottom', {$dock});
                    root.classList.toggle('topbar-pinned', {$pinned});
                    root.classList.toggle('nav-dock-min', localStorage.getItem('nav-dock-min') === 'true');
                    root.classList.toggle('table-density-compact', localStorage.getItem('table_density') === 'compact');
                })();
            </script>
            HTML;
        });
    }
}
