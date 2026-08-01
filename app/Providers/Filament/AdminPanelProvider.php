<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\Dashboard;
use App\Http\Middleware\EnsureHasPermission;
use App\Http\Middleware\UpdateLastSeen;
use App\Livewire\Admin\ManagePreferences;
use App\Services\FilamentMenuService;
use App\Support\FilamentPanelCustomizer;
use Filament\Enums\GlobalSearchPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return FilamentPanelCustomizer::apply(
            $panel
                ->default()
                ->id('admin')
                ->path('admin')
                ->login()
                ->colors(config('colors'))
                ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
                ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
                ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
                ->widgets([AccountWidget::class])
                ->pages([ManagePreferences::class, Dashboard::class])
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
                    UpdateLastSeen::class,
                ])
                ->maxContentWidth(Width::Full)
                ->globalSearch(true, position: GlobalSearchPosition::Topbar)
                ->globalSearchFieldSuffix(fn(): ?string => match (Platform::detect()) {
                    Platform::Windows, Platform::Linux => 'Ctrl+K',
                    Platform::Mac => '⌘K',
                    default => null
                })
                ->font((app()->getLocale() == 'fa') ? 'Yekan' : 'IranYekan', url: asset('build/assets/fonts/Yekan.woff'), provider: LocalFontProvider::class)
                ->userMenuItems(FilamentMenuService::getActions())
                ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
                ->globalSearchDebounce('1000ms')
                ->brandName(config('app.name'))
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
                ->databaseNotifications()
                ->databaseNotificationsPolling('60s')
                ->authMiddleware([Authenticate::class, EnsureHasPermission::class])
        );
    }
}
