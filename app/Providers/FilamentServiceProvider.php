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
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn(): string => Blade::render("@vite('resources/js/core/theme-manager.js')")
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn(): string => Blade::render("@vite('resources/js/core/filament.js')")
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn(): string => view('components.dashboard.navbars.top.palette')
                ->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => view('components.ui.loaders.screen-saver')
                ->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render('@livewire("menu.badge-manager")')
        );
    }
}
