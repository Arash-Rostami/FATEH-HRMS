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
            $field->validationMessages(function () use ($field) {
                $name = str_replace(['_', '-'], ' ', $field->getName());
                $label = $field->getLabel() ?: $name;

                return [
                    'accepted'             => __('resources/general/strings.validation.accepted', ['attribute' => $label]),
                    'active_url'           => __('resources/general/strings.validation.active_url', ['attribute' => $label]),
                    'after'                => __('resources/general/strings.validation.after', ['attribute' => $label]),
                    'after_or_equal'       => __('resources/general/strings.validation.after_or_equal', ['attribute' => $label]),
                    'alpha'                => __('resources/general/strings.validation.alpha', ['attribute' => $label]),
                    'alpha_dash'           => __('resources/general/strings.validation.alpha_dash', ['attribute' => $label]),
                    'alpha_num'            => __('resources/general/strings.validation.alpha_num', ['attribute' => $label]),
                    'array'                => __('resources/general/strings.validation.array', ['attribute' => $label]),
                    'before'               => __('resources/general/strings.validation.before', ['attribute' => $label]),
                    'before_or_equal'      => __('resources/general/strings.validation.before_or_equal', ['attribute' => $label]),
                    'between'              => __('resources/general/strings.validation.between', ['attribute' => $label]),
                    'boolean'              => __('resources/general/strings.validation.boolean', ['attribute' => $label]),
                    'confirmed'            => __('resources/general/strings.validation.confirmed', ['attribute' => $label]),
                    'current_password'     => __('resources/general/strings.validation.current_password', ['attribute' => $label]),
                    'date'                 => __('resources/general/strings.validation.date', ['attribute' => $label]),
                    'date_equals'          => __('resources/general/strings.validation.date_equals', ['attribute' => $label]),
                    'date_format'          => __('resources/general/strings.validation.date_format', ['attribute' => $label]),
                    'different'            => __('resources/general/strings.validation.different', ['attribute' => $label]),
                    'digits'               => __('resources/general/strings.validation.digits', ['attribute' => $label]),
                    'digits_between'       => __('resources/general/strings.validation.digits_between', ['attribute' => $label]),
                    'dimensions'           => __('resources/general/strings.validation.dimensions', ['attribute' => $label]),
                    'distinct'             => __('resources/general/strings.validation.distinct', ['attribute' => $label]),
                    'email'                => __('resources/general/strings.validation.email', ['attribute' => $label]),
                    'ends_with'            => __('resources/general/strings.validation.ends_with', ['attribute' => $label]),
                    'exists'               => __('resources/general/strings.validation.exists', ['attribute' => $label]),
                    'file'                 => __('resources/general/strings.validation.file', ['attribute' => $label]),
                    'filled'               => __('resources/general/strings.validation.filled', ['attribute' => $label]),
                    'gt'                   => __('resources/general/strings.validation.gt', ['attribute' => $label]),
                    'gte'                  => __('resources/general/strings.validation.gte', ['attribute' => $label]),
                    'image'                => __('resources/general/strings.validation.image', ['attribute' => $label]),
                    'in'                   => __('resources/general/strings.validation.in', ['attribute' => $label]),
                    'in_array'             => __('resources/general/strings.validation.in_array', ['attribute' => $label]),
                    'integer'              => __('resources/general/strings.validation.integer', ['attribute' => $label]),
                    'ip'                   => __('resources/general/strings.validation.ip', ['attribute' => $label]),
                    'ipv4'                 => __('resources/general/strings.validation.ipv4', ['attribute' => $label]),
                    'ipv6'                 => __('resources/general/strings.validation.ipv6', ['attribute' => $label]),
                    'json'                 => __('resources/general/strings.validation.json', ['attribute' => $label]),
                    'lt'                   => __('resources/general/strings.validation.lt', ['attribute' => $label]),
                    'lte'                  => __('resources/general/strings.validation.lte', ['attribute' => $label]),
                    'mac_address'          => __('resources/general/strings.validation.mac_address', ['attribute' => $label]),
                    'max'                  => __('resources/general/strings.validation.max', ['attribute' => $label]),
                    'mimes'                => __('resources/general/strings.validation.mimes', ['attribute' => $label]),
                    'mimetypes'            => __('resources/general/strings.validation.mimetypes', ['attribute' => $label]),
                    'min'                  => __('resources/general/strings.validation.min', ['attribute' => $label]),
                    'multiple_of'          => __('resources/general/strings.validation.multiple_of', ['attribute' => $label]),
                    'not_in'               => __('resources/general/strings.validation.not_in', ['attribute' => $label]),
                    'not_regex'            => __('resources/general/strings.validation.not_regex', ['attribute' => $label]),
                    'numeric'              => __('resources/general/strings.validation.numeric', ['attribute' => $label]),
                    'password'             => __('resources/general/strings.validation.password', ['attribute' => $label]),
                    'present'              => __('resources/general/strings.validation.present', ['attribute' => $label]),
                    'prohibited'           => __('resources/general/strings.validation.prohibited', ['attribute' => $label]),
                    'prohibited_if'        => __('resources/general/strings.validation.prohibited_if', ['attribute' => $label]),
                    'prohibited_unless'    => __('resources/general/strings.validation.prohibited_unless', ['attribute' => $label]),
                    'prohibits'            => __('resources/general/strings.validation.prohibits', ['attribute' => $label]),
                    'regex'                => __('resources/general/strings.validation.regex', ['attribute' => $label]),
                    'required'             => __('resources/general/strings.validation.required', ['attribute' => $label]),
                    'required_array_keys'  => __('resources/general/strings.validation.required_array_keys', ['attribute' => $label]),
                    'required_if'          => __('resources/general/strings.validation.required_if', ['attribute' => $label]),
                    'required_unless'      => __('resources/general/strings.validation.required_unless', ['attribute' => $label]),
                    'required_with'        => __('resources/general/strings.validation.required_with', ['attribute' => $label]),
                    'required_with_all'    => __('resources/general/strings.validation.required_with_all', ['attribute' => $label]),
                    'required_without'     => __('resources/general/strings.validation.required_without', ['attribute' => $label]),
                    'required_without_all' => __('resources/general/strings.validation.required_without_all', ['attribute' => $label]),
                    'same'                 => __('resources/general/strings.validation.same', ['attribute' => $label]),
                    'size'                 => __('resources/general/strings.validation.size', ['attribute' => $label]),
                    'starts_with'          => __('resources/general/strings.validation.starts_with', ['attribute' => $label]),
                    'string'               => __('resources/general/strings.validation.string', ['attribute' => $label]),
                    'timezone'             => __('resources/general/strings.validation.timezone', ['attribute' => $label]),
                    'unique'               => __('resources/general/strings.validation.unique', ['attribute' => $label]),
                    'uploaded'             => __('resources/general/strings.validation.uploaded', ['attribute' => $label]),
                    'url'                  => __('resources/general/strings.validation.url', ['attribute' => $label]),
                    'uuid'                 => __('resources/general/strings.validation.uuid', ['attribute' => $label]),
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
