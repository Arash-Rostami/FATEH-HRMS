<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use App\Models\Department;
use App\Models\Ticket;
use App\Rules\NoCyclicDepartmentHierarchy;
use App\Traits\FilamentFormDivider;
use App\Traits\FilamentIconOptions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class DepartmentFormPresenter
{
    use FilamentFormDivider, FilamentIconOptions;

    public static function level(): Select
    {
        return Select::make('level')
            ->label(__('resources/department/strings.fields.level'))
            ->options(fn(Get $get): array => filled($get('subordinate_to'))
                ? [
                    1 => __('resources/department/strings.fields.level_1'),
                    2 => __('resources/department/strings.fields.level_2'),
                ]
                : [
                    0 => __('resources/department/strings.fields.level_0'),
                    1 => __('resources/department/strings.fields.level_1'),
                    2 => __('resources/department/strings.fields.level_2'),
                ])
            ->native(false)
            ->required()
            ->default(0)
            ->live()
            ->helperText(__('resources/department/strings.hints.level'));
    }

    public static function subordinateTo(): Select
    {
        return Select::make('subordinate_to')
            ->label(__('resources/department/strings.fields.subordinate_to'))
            ->options(fn(Get $get): array => Department::getCachedOptions()->except($get('code'))->all())
            ->searchable()
            ->native(false)
            ->nullable()
            ->live()
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                if ($state !== null && (int) $get('level') === 0) {
                    $set('level', 1);
                }
            })
            ->rules(fn(Get $get) => [new NoCyclicDepartmentHierarchy($get('code'))])
            ->helperText(__('resources/department/strings.hints.subordinate_to'));
    }

    public static function code(): TextInput
    {
        return TextInput::make('code')
            ->label(__('resources/department/strings.fields.code'))
            ->required()
            ->maxLength(10)
            ->unique(ignoreRecord: true)
            ->alphaDash()
            ->helperText(__('resources/department/strings.hints.code'));
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/department/strings.fields.description'))
            ->required()
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
            ->helperText(__('resources/department/strings.hints.name'));
    }

    public static function sections(): TagsInput
    {
        return TagsInput::make('sections')
            ->label(__('resources/department/strings.fields.sections'))
            ->placeholder(__('resources/department/strings.fields.sections_placeholder'))
            ->helperText(__('resources/department/strings.hints.sections'))
            ->splitKeys(['Enter', ',', ' ']);
    }

    public static function ticketOptions(): Repeater
    {
        return Repeater::make('ticket_options')
            ->label(__('resources/department/strings.fields.ticket_options'))
            ->schema([
                TextInput::make('request_type')
                    ->label(__('resources/department/strings.fields.request_type'))
                    ->datalist(array_keys(Ticket::$requestTypeOptions))
                    ->required(),

                TextInput::make('area_key')
                    ->label(__('resources/department/strings.fields.area_key'))
                    ->placeholder(__('resources/department/strings.fields.area_key_placeholder'))
                    ->required()
                    ->regex('/^[a-zA-Z0-9_-]+$/'),

                TextInput::make('area_label')
                    ->label(__('resources/department/strings.fields.area_label'))
                    ->required(),

                Select::make('icon')
                    ->label(__('resources/department/strings.fields.icon'))
                    ->helperText(__('resources/department/strings.hints.icon'))
                    ->options(fn() => static::curatedIconOptions())
                    ->native(false)
                    ->lazy()
                    ->allowHtml()
                    ->getOptionLabelUsing(fn(string $value) => static::curatedIconOptionLabel($value)),
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
            ->label(__('resources/department/strings.fields.units'))
            ->placeholder(__('resources/department/strings.fields.units_placeholder'))
            ->helperText(__('resources/department/strings.hints.units'))
            ->splitKeys(['Enter', ',', ' ']);
    }
}
