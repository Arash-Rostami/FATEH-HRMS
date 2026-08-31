<?php

namespace App\Support;

use Filament\Enums\UserMenuPosition;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class FilamentPanelCustomizer
{
    public static function apply(Panel $panel): Panel
    {
        return $panel
            ->brandLogo(fn() => self::logoHtml(false))
            ->darkModeBrandLogo(fn() => self::logoHtml(true))
            ->favicon(fn() => asset(config('app.favicon')))
            ->renderHook(PanelsRenderHook::HEAD_END, fn() => self::tenantBackgroundStyle())
            ->sidebarCollapsibleOnDesktop(fn() => self::pref('sidebar_collapsible', false) || self::pref('nav_dock', false))
            ->sidebarFullyCollapsibleOnDesktop(fn() => self::pref('sidebar_fully_collapsible', false))
            ->breadcrumbs(fn() => self::pref('breadcrumbs', true))
            ->collapsibleNavigationGroups(fn() => self::pref('collapsible_groups', true))
            ->topNavigation(fn() => self::pref('top_nav', false))
            ->unsavedChangesAlerts(fn() => self::pref('unsaved_changes_alerts', true))
            ->topbar(fn() => self::pref('topbar', true))
            ->spa(fn() => self::pref('spa_enabled', true))
            ->userMenu(position: fn() => self::pref('user_menu_topbar', false) ? UserMenuPosition::Topbar : UserMenuPosition::Sidebar);
    }

    private static function pref(string $key, mixed $default = false): mixed
    {
        $preferences = Auth::check() ? (Auth::user()->extra['preferences'] ?? []) : [];

        return $preferences[$key] ?? $default;
    }

    private static function tenantBackgroundStyle(): HtmlString
    {
        if (!request()->routeIs('filament.admin.auth.login')) {
            return new HtmlString('');
        }

        return new HtmlString(sprintf(
            '<style>:root{--tenant-bg-image:url("%s")}</style>',
            addcslashes(asset(config('app.admin_background_image')), '"\\')
        ));
    }

    private static function logoHtml(bool $dark): HtmlString
    {
        $name = e(config('app.name_en'));

        return new HtmlString(sprintf(
            '<img src="%s" alt="%s" title="%s" style="height:2rem;width:auto;margin:auto" />',
            e(asset(tenantLogo($dark, 'admin'))), $name, $name
        ));
    }
}
