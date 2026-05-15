<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureHasPermission;
use App\Livewire\Admin\ManagePreferences;
use Filament\Actions\Action;
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
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
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
            ->pages([ManagePreferences::class, Dashboard::class,])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([AccountWidget::class])
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
                Platform::Windows,
                Platform::Linux => 'Ctrl+K',
                Platform::Mac => '⌘K',
                default => null,
            })
            ->font((app()->getLocale() == 'fa') ? 'Yekan' : 'IranYekan',
                url: asset('build/assets/fonts/Yekan.woff'),
                provider: LocalFontProvider::class
            )
            ->userMenu(position: UserMenuPosition::Topbar)
            ->userMenuItems([
                Action::make('dashboard')
                    ->label('پنل کاربر')
                    ->url(fn() => route('dashboard'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-squares-2x2'),
                Action::make('preferences')
                    ->label('تنظیمات')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->url(fn(): string => ManagePreferences::getUrl()),
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('1000ms')
            ->brandName('HRMS')
            ->brandLogo(asset('build/assets/img/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->maxContentWidth(Width::Full)
            ->databaseTransactions()
            ->darkMode(false)
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
            ->userMenu(position: fn() => $this->getPreference('user_menu_topbar', false)
                ? UserMenuPosition::Topbar : UserMenuPosition::Sidebar
            )
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
