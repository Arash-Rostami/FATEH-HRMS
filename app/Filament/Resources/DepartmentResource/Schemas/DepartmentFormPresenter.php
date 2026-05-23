<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

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
}
