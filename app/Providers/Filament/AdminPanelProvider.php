<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AccountWidget;
use App\Http\Middleware\EnsureHasPermission;
use App\Livewire\Admin\ManagePreferences;
use App\Services\FilamentMenuService;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Widgets\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
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
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([AccountWidget::class])
            ->pages([ManagePreferences::class, Dashboard::class,])
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
            ->globalSearch(true, position: GlobalSearchPosition::Topbar)
            ->globalSearchFieldSuffix(fn(): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'Ctrl+K',
                Platform::Mac => '⌘K',
                default => null
            })
            ->font((app()->getLocale() == 'fa') ? 'Yekan' : 'IranYekan', url: asset('build/assets/fonts/Yekan.woff'), provider: LocalFontProvider::class)
            ->userMenu(position: UserMenuPosition::Topbar)
            ->userMenuItems(FilamentMenuService::getActions())
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('1000ms')
            ->brandName('HRMS')
            ->brandLogo(asset('build/assets/img/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->maxContentWidth(Width::Full)
            ->databaseTransactions()
            ->darkMode(false)
            ->navigationGroups([
                __('resources/user/strings.navigation.group'),
                __('resources/reservation/strings.nav_group'),
                __('resources/gallery/strings.nav_group'),
                __('resources/ths/strings.nav_group')
            ])
            ->subNavigationPosition(SubNavigationPosition::End)
            ->viteTheme('resources/css/core/filament.css')
            ->sidebarCollapsibleOnDesktop(fn() => $this->getPreference('sidebar_collapsible', false))
            ->sidebarFullyCollapsibleOnDesktop(fn() => $this->getPreference('sidebar_fully_collapsible', false))
            ->breadcrumbs(fn() => $this->getPreference('breadcrumbs', true))
            ->collapsibleNavigationGroups(fn() => $this->getPreference('collapsible_groups', true))
            ->topNavigation(fn() => $this->getPreference('top_nav', false))
            ->unsavedChangesAlerts(fn() => $this->getPreference('unsaved_changes_alerts', true))
            ->topbar(fn() => $this->getPreference('topbar', true))
            ->spa(fn() => $this->getPreference('spa_enabled', true))
            ->userMenu(position: fn() => $this->getPreference('user_menu_topbar', false) ? UserMenuPosition::Topbar : UserMenuPosition::Sidebar)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->authMiddleware([Authenticate::class, EnsureHasPermission::class]);
    }

    private function getPreference(string $key, mixed $default = false): mixed
    {
        $preferences = Auth::check() ? (Auth::user()->extra['preferences'] ?? []) : [];

        return $preferences[$key] ?? $default;
    }
}
