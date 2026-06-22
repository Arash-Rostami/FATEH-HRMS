<?php

namespace App\Filament\Resources\EventResource\Schemas;

use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\{Select, Textarea, TextInput, TimePicker, Toggle};
use Filament\Schemas\Components\FusedGroup;

class EventFormPresenter
{
    use FilamentFormDivider;
    public static function dateJalali(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'date_jalali',
            label: __('resources/event/strings.fields.date'),
            required: true,
            yearFrom: 1400,
            fullWidth: false,
        )->columnSpanFull();
    }

    public static function dateTimePart(): TimePicker
    {
        return TimePicker::make('date_time_part')
            ->validationMessages([
                'required' => __('resources/event/strings.validation.generic.required'),
                'unique' => __('resources/event/strings.validation.generic.unique'),
                'max' => __('resources/event/strings.validation.generic.max'),
                'min' => __('resources/event/strings.validation.generic.min'),
                'email' => __('resources/event/strings.validation.generic.email'),
                'numeric' => __('resources/event/strings.validation.generic.numeric'),
                'mimes' => __('resources/event/strings.validation.generic.mimes'),
                'url' => __('resources/event/strings.validation.generic.url'),
                'in' => __('resources/event/strings.validation.generic.in'),
                'exists' => __('resources/event/strings.validation.generic.exists')
            ])
            ->label(__('resources/event/strings.fields.time'))
            ->native(false)
            ->columnSpan(2)
            ->required()
            ->default('08:00')
            ->helperText(__('resources/event/strings.hints.date_time_part'))
            ->validationMessages([
                'required' => __('resources/event/strings.validation.date_time_part.required'),
            ]);
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->validationMessages([
                'required' => __('resources/event/strings.validation.generic.required'),
                'unique' => __('resources/event/strings.validation.generic.unique'),
                'max' => __('resources/event/strings.validation.generic.max'),
                'min' => __('resources/event/strings.validation.generic.min'),
                'email' => __('resources/event/strings.validation.generic.email'),
                'numeric' => __('resources/event/strings.validation.generic.numeric'),
                'mimes' => __('resources/event/strings.validation.generic.mimes'),
                'url' => __('resources/event/strings.validation.generic.url'),
                'in' => __('resources/event/strings.validation.generic.in'),
                'exists' => __('resources/event/strings.validation.generic.exists')
            ])
            ->label(__('resources/event/strings.fields.description'))
            ->rows(5)
            ->maxLength(3000)
            ->helperText(__('resources/event/strings.hints.description'))
            ->validationMessages([
                'max' => __('resources/event/strings.validation.description.max_length'),
            ])
            ->columnSpanFull();
    }

    public static function private(): Toggle
    {
        return Toggle::make('private')
            ->validationMessages([
                'required' => __('resources/event/strings.validation.generic.required'),
                'unique' => __('resources/event/strings.validation.generic.unique'),
                'max' => __('resources/event/strings.validation.generic.max'),
                'min' => __('resources/event/strings.validation.generic.min'),
                'email' => __('resources/event/strings.validation.generic.email'),
                'numeric' => __('resources/event/strings.validation.generic.numeric'),
                'mimes' => __('resources/event/strings.validation.generic.mimes'),
                'url' => __('resources/event/strings.validation.generic.url'),
                'in' => __('resources/event/strings.validation.generic.in'),
                'exists' => __('resources/event/strings.validation.generic.exists')
            ])
            ->label(__('resources/event/strings.fields.private'))
            ->helperText(__('resources/event/strings.fields.private_hint'))
            ->live()
            ->inline(false)
            ->default(false);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->validationMessages([
                'required' => __('resources/event/strings.validation.generic.required'),
                'unique' => __('resources/event/strings.validation.generic.unique'),
                'max' => __('resources/event/strings.validation.generic.max'),
                'min' => __('resources/event/strings.validation.generic.min'),
                'email' => __('resources/event/strings.validation.generic.email'),
                'numeric' => __('resources/event/strings.validation.generic.numeric'),
                'mimes' => __('resources/event/strings.validation.generic.mimes'),
                'url' => __('resources/event/strings.validation.generic.url'),
                'in' => __('resources/event/strings.validation.generic.in'),
                'exists' => __('resources/event/strings.validation.generic.exists')
            ])
            ->label(__('resources/event/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/event/strings.hints.title'))
            ->validationMessages([
                'required' => __('resources/event/strings.validation.title.required'),
                'max'      => __('resources/event/strings.validation.title.max_length'),
            ]);
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->validationMessages([
                'required' => __('resources/event/strings.validation.generic.required'),
                'unique' => __('resources/event/strings.validation.generic.unique'),
                'max' => __('resources/event/strings.validation.generic.max'),
                'min' => __('resources/event/strings.validation.generic.min'),
                'email' => __('resources/event/strings.validation.generic.email'),
                'numeric' => __('resources/event/strings.validation.generic.numeric'),
                'mimes' => __('resources/event/strings.validation.generic.mimes'),
                'url' => __('resources/event/strings.validation.generic.url'),
                'in' => __('resources/event/strings.validation.generic.in'),
                'exists' => __('resources/event/strings.validation.generic.exists')
            ])
            ->label(__('resources/event/strings.fields.user'))
            ->helperText(__('resources/event/strings.fields.user_hint'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->required(fn($get) => (bool)$get('private'))
            ->visible(fn($get) => (bool)$get('private'))
            ->validationMessages([
                'required' => __('resources/event/strings.validation.user_id.required'),
            ]);
    }
}
