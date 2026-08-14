<?php

namespace App\Filament\Resources\EventResource\Schemas;

use App\Models\Event;
use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\{Repeater, Select, Textarea, TextInput, TimePicker, Toggle};
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
            ->label(__('resources/event/strings.fields.time'))
            ->native(false)
            ->columnSpan(2)
            ->required()
            ->default('08:00')
            ->helperText(__('resources/event/strings.hints.date_time_part'));
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/event/strings.fields.description'))
            ->rows(5)
            ->maxLength(3000)
            ->helperText(__('resources/event/strings.hints.description'))
            ->columnSpanFull();
    }

    public static function private(): Toggle
    {
        return Toggle::make('private')
            ->label(__('resources/event/strings.fields.private'))
            ->helperText(__('resources/event/strings.fields.private_hint'))
            ->live()
            ->inline(false)
            ->default(false);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/event/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/event/strings.hints.title'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/event/strings.fields.user'))
            ->helperText(__('resources/event/strings.fields.user_hint'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->required(fn($get) => (bool)$get('private'))
            ->visible(fn($get) => (bool)$get('private'));
    }

    public static function remindHours(): Select
    {
        return Select::make('remind_hours')
            ->label(__('resources/event/strings.fields.remind_hours'))
            ->helperText(__('resources/event/strings.fields.remind_hours_hint'))
            ->options(collect(Event::REMIND_HOURS_OPTIONS)->mapWithKeys(
                fn(int $hours) => [$hours => "{$hours} ساعت قبل"]
            ))
            ->native(false)
            ->nullable();
    }

    public static function countdownEnabled(): Toggle
    {
        return Toggle::make('enabled')
            ->label(__('resources/event/strings.fields.enabled'))
            ->helperText(__('resources/event/strings.hints.countdown'))
            ->live()
            ->default(false)
            ->inline(false);
    }

    public static function countdownMood(): Select
    {
        return Select::make('mood')
            ->label(__('resources/event/strings.fields.mood'))
            ->helperText(__('resources/event/strings.hints.mood'))
            ->options([
                'happy' => __('resources/event/strings.fields.mood_happy'),
                'mourning' => __('resources/event/strings.fields.mood_mourning'),
            ])
            ->default('happy')
            ->native(false)
            ->live()
            ->visible(fn($get) => (bool) $get('enabled'))
            ->dehydrated(fn($get) => (bool) $get('enabled'));
    }

    public static function countdownConfetti(): Toggle
    {
        return Toggle::make('confetti')
            ->label(__('resources/event/strings.fields.confetti'))
            ->helperText(__('resources/event/strings.hints.confetti'))
            ->live()
            ->default(true)
            ->inline(false)
            ->visible(fn($get) => (bool) $get('enabled'))
            ->dehydrated(fn($get) => (bool) $get('enabled'));
    }

    public static function countdownMessages(): Repeater
    {
        return Repeater::make('messages')
            ->label(__('resources/event/strings.fields.messages'))
            ->schema([
                TextInput::make('message')
                    ->label(__('resources/event/strings.fields.message'))
                    ->maxLength(255),
            ])
            ->defaultItems(0)
            ->maxItems(8)
            ->addable()
            ->deletable()
            ->visible(fn($get) => (bool) $get('enabled'))
            ->dehydrated(fn($get) => (bool) $get('enabled'))
            ->addActionLabel(__('resources/event/strings.fields.message'));
    }
}