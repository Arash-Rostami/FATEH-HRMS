<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use App\Models\Ticket;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Cache;

class DepartmentFormPresenter
{
    use FilamentFormDivider;

    public static function code(): TextInput
    {
        return TextInput::make('code')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.code'))
            ->required()
            ->maxLength(10)
            ->unique(ignoreRecord: true)
            ->alphaDash()
            ->helperText(__('resources/department/strings.hints.code'))
            ->validationMessages([
                'required'   => __('resources/department/strings.validation.code_required'),
                'max'        => __('resources/department/strings.validation.code_max'),
                'unique'     => __('resources/department/strings.validation.code_unique'),
                'alpha_dash' => __('resources/department/strings.validation.code_alpha_dash'),
            ]);
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.description'))
            ->nullable()
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/department/strings.hints.description'));
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/department/strings.hints.name'))
            ->validationMessages([
                'required' => __('resources/department/strings.validation.name_required'),
                'max'      => __('resources/department/strings.validation.name_max'),
            ]);
    }

    public static function sections(): TagsInput
    {
        return TagsInput::make('sections')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.sections'))
            ->placeholder(__('resources/department/strings.fields.sections_placeholder'))
            ->helperText(__('resources/department/strings.hints.sections'))
            ->splitKeys(['Enter', ',', ' ']);
    }

    public static function ticketOptions(): Repeater
    {
        return Repeater::make('ticket_options')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.ticket_options'))
            ->schema([
                TextInput::make('request_type')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
                    ->label(__('resources/department/strings.fields.request_type'))
                    ->datalist(array_keys(Ticket::$requestTypeOptions))
                    ->required()
                    ->validationMessages([
                        'required' => __('resources/department/strings.validation.request_type_required'),
                    ]),

                TextInput::make('area_key')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
                    ->label(__('resources/department/strings.fields.area_key'))
                    ->placeholder(__('resources/department/strings.fields.area_key_placeholder'))
                    ->required()
                    ->regex('/^[a-zA-Z0-9_-]+$/')
                    ->validationMessages([
                        'required' => __('resources/department/strings.validation.area_key_required'),
                        'regex'    => __('resources/department/strings.validation.area_key_regex'),
                    ]),

                TextInput::make('area_label')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
                    ->label(__('resources/department/strings.fields.area_label'))
                    ->required()
                    ->validationMessages([
                        'required' => __('resources/department/strings.validation.area_label_required'),
                    ]),

                Select::make('icon')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
                    ->label(__('resources/department/strings.fields.icon'))
                    ->helperText(__('resources/department/strings.hints.icon'))
                    ->options(fn () => static::getAllIcons())
                    ->native(false)
                    ->lazy()
                    ->allowHtml()
                    ->getOptionLabelUsing(fn (string $value) => static::getIconOptionLabel($value)),
            ])
            ->columns(4)
            ->defaultItems(0)
            ->columnSpanFull()
            ->collapsible()
            ->reorderableWithButtons()
            ->helperText(__('resources/department/strings.hints.ticket_options'));
    }

    public static function units(): TagsInput
    {
        return TagsInput::make('units')
            ->validationMessages([
                'required' => __('resources/department/strings.validation.generic.required'),
                'unique' => __('resources/department/strings.validation.generic.unique'),
                'max' => __('resources/department/strings.validation.generic.max'),
                'min' => __('resources/department/strings.validation.generic.min'),
                'email' => __('resources/department/strings.validation.generic.email'),
                'numeric' => __('resources/department/strings.validation.generic.numeric'),
                'mimes' => __('resources/department/strings.validation.generic.mimes'),
                'url' => __('resources/department/strings.validation.generic.url'),
                'in' => __('resources/department/strings.validation.generic.in'),
                'exists' => __('resources/department/strings.validation.generic.exists')
            ])
            ->label(__('resources/department/strings.fields.units'))
            ->placeholder(__('resources/department/strings.fields.units_placeholder'))
            ->helperText(__('resources/department/strings.hints.units'))
            ->splitKeys(['Enter', ',', ' ']);
    }

    private static function formatIconSvg(string $svg): string
    {
        $svg = preg_replace('/\s+(width|height)="[^"]*"/', '', $svg);

        return str_replace('<svg', '<svg class="w-4 h-4"', $svg);
    }

    private static function getIconOptionLabel(string $value): string
    {
        $name = str_replace('heroicon-', '', $value);
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);
        $file = base_path("vendor/blade-ui-kit/blade-heroicons/resources/svg/{$name}.svg");

        return static::formatIconSvg(file_exists($file) && is_file($file) ? file_get_contents($file) : '');
    }

    private static function getAllIcons(): array
    {
        return Cache::remember('department_all_icons', 86400, function () {
            $options = [];
            $files = glob(base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/*.svg'));

            foreach ($files as $file) {
                if (is_file($file)) {
                    $name = basename($file, '.svg');
                    $options["heroicon-{$name}"] = static::formatIconSvg(file_get_contents($file));
                }
            }

            return $options;
        });
    }
}
