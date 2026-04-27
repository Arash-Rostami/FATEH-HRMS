<?php

namespace App\Providers\Filament;

use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(config('colors'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->maxContentWidth(Width::Full)
            ->spa()
            ->globalSearch(true, position: GlobalSearchPosition::Topbar)
            ->globalSearchFieldSuffix(fn(): ?string => match (Platform::detect()) {
                Platform::Windows,
                Platform::Linux => 'Ctrl+K',
                Platform::Mac => '⌘K',
                default => null,
            })
            ->font((app()->getLocale() == 'fa') ? 'Yekan' : 'IranYekan',
                url: asset('build/assets/fonts/Yekan.woff'),
                provider: LocalFontProvider::class
            )
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('1000ms')
            ->breadcrumbs()
            ->brandName('HRMS')
            ->brandLogo(asset('build/assets/img/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->maxContentWidth(Width::Full)
            ->databaseTransactions()
            ->collapsibleNavigationGroups()
            ->sidebarCollapsibleOnDesktop()
            ->subNavigationPosition(SubNavigationPosition::End)
            ->viteTheme('resources/css/core/filament.css')
            ->renderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render("@vite('resources/js/admin/filament-theme-adapter.js')")
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('components.admin.navbar.palette-controler')->render(),
            )

            ->authMiddleware([Authenticate::class]);
    }
}
