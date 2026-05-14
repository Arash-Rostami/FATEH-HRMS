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
            function (): string {
                $user = auth()->user();
                $preferences = $user?->extra['preferences'] ?? [];

                $isScreenSaverEnabled = $user && (isset($preferences['screen_saver']) ? $preferences['screen_saver'] : true);

                if (!$isScreenSaverEnabled) {
                    return '';
                }

                $timeout = isset($preferences['screen_saver_timeout']) ? (int)$preferences['screen_saver_timeout'] : 60;

                return view('components.ui.loaders.screen-saver', ['timeout' => $timeout])->render();
            }
        );
    }
}
