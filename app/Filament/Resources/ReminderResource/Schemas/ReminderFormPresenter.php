<?php

namespace App\Filament\Resources\ReminderResource\Schemas;

use App\Enums\ReminderRecurrence;
use App\Services\PersianDateFieldService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Morilog\Jalali\Jalalian;

class ReminderFormPresenter
{
    public static function dueDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'due_at_date',
            label: __('resources/reminder/strings.fields.due_date'),
            required: true,
            yearFrom: 1400,
            yearTo: Jalalian::now()->getYear() + 5,
            fullWidth: false,
        );
    }

    public static function dueTime(): TextInput
    {
        return TextInput::make('due_at_time')
            ->label(__('resources/reminder/strings.fields.due_time'))
            ->type('time')
            ->default('09:00')
            ->required();
    }

    public static function notes(): Textarea
    {
        return Textarea::make('notes')
            ->label(__('resources/reminder/strings.fields.notes'))
            ->nullable()
            ->maxLength(1000)
            ->rows(3)
            ->columnSpanFull()
            ->placeholder(__('resources/reminder/strings.placeholders.notes'));
    }

    public static function recurs(): Select
    {
        return Select::make('recurs')
            ->label(__('resources/reminder/strings.fields.recurs'))
            ->options(collect(ReminderRecurrence::cases())->mapWithKeys(fn(ReminderRecurrence $case) => [$case->value => $case->label()]))
            ->default(ReminderRecurrence::None->value)
            ->native(false)
            ->required();
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/reminder/strings.fields.title'))
            ->required()
            ->maxLength(191)
            ->columnSpanFull()
            ->placeholder(__('resources/reminder/strings.placeholders.title'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/reminder/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->native(false)
            ->required();
    }
}
