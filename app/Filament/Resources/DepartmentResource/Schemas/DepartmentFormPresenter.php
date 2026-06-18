<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

class DepartmentFormPresenter
{
    use FilamentFormDivider;
    public static function code(): TextInput
    {
        return TextInput::make('code')
            ->label(__('resources/department/strings.fields.code'))
            ->required()
            ->maxLength(10)
            ->unique(ignoreRecord: true)
            ->alphaDash()
            ->helperText(__('resources/department/strings.hints.code'))
            ->validationMessages([
                'required' => __('resources/department/strings.validation.code_required'),
                'max' => __('resources/department/strings.validation.code_max'),
                'unique' => __('resources/department/strings.validation.code_unique'),
                'alpha_dash' => __('resources/department/strings.validation.code_alpha_dash'),
            ]);
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/department/strings.fields.description'))
            ->nullable()
            ->rows(3)
            ->columnSpanFull()
            ->helperText(__('resources/department/strings.hints.description'));
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/department/strings.fields.name'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/department/strings.hints.name'))
            ->validationMessages([
                'required' => __('resources/department/strings.validation.name_required'),
                'max' => __('resources/department/strings.validation.name_max'),
            ]);
    }


    public static function ticketOptions(): Repeater
    {
        return Repeater::make('ticket_options')
            ->label(__('resources/department/strings.fields.ticket_options'))
            ->schema([
                TextInput::make('request_type')
                    ->label('نوع درخواست')
                    ->datalist(array_keys(\App\Models\Ticket::$requestTypeOptions))
                    ->required(),
                TextInput::make('area_key')
                    ->label('کلید حوزه (انگلیسی)')
                    ->required(),
                TextInput::make('area_label')
                    ->label('عنوان حوزه (فارسی)')
                    ->required(),
                Select::make('icon')
                    ->label(__('resources/department/strings.fields.icon') ?? 'آیکون (اختیاری)')
                    ->helperText(__('resources/department/strings.hints.icon') ?? '')
                    ->searchable()
                    ->native(false)
                    ->allowHtml()
                    ->getSearchResultsUsing(fn(string $search) => static::getIconSearchResults($search))
                    ->getOptionLabelUsing(fn(string $value) => static::getIconOptionLabel($value)),
            ])
            ->columns(4)
            ->defaultItems(0)
            ->columnSpanFull()
            ->collapsible()
            ->reorderableWithButtons()
            ->helperText(__('resources/department/strings.hints.ticket_options'));
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

    private static function getIconSearchResults(string $search): array
    {
        $search = Str::slug($search);

        if (empty($search)) {
            return [];
        }

        $options = [];
        $files = glob(base_path("vendor/blade-ui-kit/blade-heroicons/resources/svg/*{$search}*.svg"));

        foreach ($files as $file) {
            if (is_file($file)) {
                $name = basename($file, '.svg');
                $options["heroicon-{$name}"] = static::formatIconSvg(file_get_contents($file));
            }
        }

        return $options;
    }
}
