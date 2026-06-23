<?php

namespace App\Providers;

use Filament\Forms\Components\Field;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerCoreScripts();
        $this->registerComponentHooks();
        $this->configureFilamentComponents();
    }

    private function configureFilamentComponents(): void
    {
        Field::configureUsing(function (Field $field): void {
            // Wait until the field is fully configured before inspecting its rules
            $field->validationMessages(function () use ($field) {
                $name = str_replace(['_', '-'], ' ', $field->getName());
                // In Persian, we might just say "فیلد انتخابی" if we don't have a specific label yet.
                // We'll use the label if available.
                $label = $field->getLabel() ?: $name;

                return [
                    'required' => __('resources/general/strings.validation.required', ['attribute' => $label]),
                    'exists'   => __('resources/general/strings.validation.exists', ['attribute' => $label]),
                    'in'       => __('resources/general/strings.validation.in', ['attribute' => $label]),
                    'unique'   => __('resources/general/strings.validation.unique', ['attribute' => $label]),
                    'max'      => __('resources/general/strings.validation.max', ['attribute' => $label]),
                    'min'      => __('resources/general/strings.validation.min', ['attribute' => $label]),
                    'mimes'    => __('resources/general/strings.validation.mimes', ['attribute' => $label]),
                    'email'    => __('resources/general/strings.validation.email', ['attribute' => $label]),
                    'url'      => __('resources/general/strings.validation.url', ['attribute' => $label]),
                    'numeric'  => __('resources/general/strings.validation.numeric', ['attribute' => $label]),
                    'date'     => __('resources/general/strings.validation.date', ['attribute' => $label]),
                ];
            });
        });
    }

    private function registerComponentHooks(): void
    {
        $hooks = [
            [PanelsRenderHook::GLOBAL_SEARCH_AFTER, 'components.dashboard.navbars.top.palette'],
            [PanelsRenderHook::GLOBAL_SEARCH_START, 'filament.resources.dashboard.utilities'],
            [PanelsRenderHook::BODY_END, 'components.ui.loaders.screen-saver'],
            [PanelsRenderHook::BODY_START, 'components.ui.decor.panel-ghost'],
            [PanelsRenderHook::BODY_END, 'components.ui.loaders.spinner'],
        ];

        foreach ($hooks as [$hook, $view]) {
            FilamentView::registerRenderHook(
                $hook,
                fn(): string => view($view)->render()
            );
        }
    }

    private function registerCoreScripts(): void
    {
        $scripts = [
            'resources/js/core/theme-manager.js',
            'resources/js/core/filament.js',
            'resources/js/components/alpine/stores/filament-menu.js',
        ];

        foreach ($scripts as $script) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn(): string => Blade::render(sprintf("@vite('%s')", $script))
            );
        }
    }
}
