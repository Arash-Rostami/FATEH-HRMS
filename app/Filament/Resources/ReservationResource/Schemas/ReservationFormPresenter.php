<?php

namespace App\Filament\Resources\ReservationResource\Schemas;

use App\Enums\CancelReason;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ReservationFormPresenter
{
    public static function cancelReason(): Select
    {
        return Select::make('cancel_reason')
            ->label(__('resources/reservation/strings.fields.cancel_reason'))
            ->options(CancelReason::class)
            ->nullable()
            ->live()
            ->native(false)
            ->placeholder(__('resources/reservation/strings.fields.cancel_reason_placeholder'))
            ->columnSpanFull()
            ->visible(function (Get $get) {
                $status = $get('status');
                return in_array(
                    $status instanceof \BackedEnum ? $status->value : $status,
                    ['cancelled_user', 'cancelled_admin']
                );
            })
            ->validationMessages([
                'in' => __('resources/reservation/strings.validation.cancel_reason_in'),
            ]);
    }

    public static function endTime(): DateTimePicker
    {
        return DateTimePicker::make('end_time')
            ->label(__('resources/reservation/strings.fields.end_time'))
            ->required(fn(Get $get) => !$get('is_full_day'))
            ->seconds(false)
            ->native(false)
            ->locale('fa')
            ->after('start_time')
            ->visible(fn(Get $get) => !$get('is_full_day'))
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.end_time_required'),
                'after' => __('resources/reservation/strings.validation.end_after_start'),
                'date' => __('resources/reservation/strings.validation.end_time_date'),
            ]);
    }

    public static function isFullDay(): Toggle
    {
        return Toggle::make('is_full_day')
            ->label(__('resources/reservation/strings.fields.is_full_day'))
            ->onColor('success')
            ->columnSpanFull()
            ->live();
    }

    public static function isRecurring(): Toggle
    {
        return Toggle::make('is_recurring')
            ->label(__('resources/reservation/strings.fields.is_recurring'))
            ->dehydrated(false)
            ->afterStateUpdated(fn(Set $set, bool $state) => $state && $set('recur_pattern', 'daily'))
            ->live()
            ->columnSpanFull()
            ->default(false);
    }

    public static function parentId(): Select
    {
        return Select::make('parent_id')
            ->label(__('resources/reservation/strings.fields.parent_id'))
            ->helperText(__('resources/reservation/strings.descriptions.parent_id'))
            ->options(fn() => Reservation::roots()->with(['resource', 'user'])->get()->pluck('resource_dropdown_label', 'id'))
            ->native(false)
            ->searchable()
            ->nullable()
            ->columnSpanFull()
            ->placeholder('—')
            ->validationMessages([
                'in' => __('resources/reservation/strings.validation.parent_id_in'),
            ]);
    }

    public static function recurCount(): TextInput
    {
        return TextInput::make('recur_count')
            ->label(__('resources/reservation/strings.fields.recur_count'))
            ->numeric()
            ->minValue(2)
            ->maxValue(52)
            ->default(4)
            ->dehydrated(false)
            ->visible(fn(Get $get) => (bool)$get('is_recurring'))
            ->required(fn(Get $get) => (bool)$get('is_recurring'))
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.recur_count_required'),
                'numeric' => __('resources/reservation/strings.validation.recur_count_numeric'),
                'min' => __('resources/reservation/strings.validation.recur_count_min'),
                'max' => __('resources/reservation/strings.validation.recur_count_max'),
            ]);
    }

    public static function recurPattern(): Select
    {
        return Select::make('recur_pattern')
            ->label(__('resources/reservation/strings.fields.recur_pattern'))
            ->options(__('resources/reservation/strings.recur_patterns'))
            ->native(false)
            ->default('daily')
            ->dehydrated(false)
            ->visible(fn(Get $get) => (bool)$get('is_recurring'))
            ->required(fn(Get $get) => (bool)$get('is_recurring'))
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.recur_pattern_required'),
                'in' => __('resources/reservation/strings.validation.recur_pattern_in'),
            ]);
    }

    public static function resourceId(): Select
    {
        return Select::make('resource_id')
            ->label(__('resources/reservation/strings.fields.resource'))
            ->options(fn() => Resource::orderBy('name')->get()->pluck('labeled_name', 'id'))
            ->searchable()
            ->preload()
            ->required()
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.resource_required'),
                'in' => __('resources/reservation/strings.validation.resource_in'),
            ]);
    }

    public static function startTime(): DateTimePicker
    {
        return DateTimePicker::make('start_time')
            ->label(__('resources/reservation/strings.fields.start_time'))
            ->required(fn(Get $get) => !$get('is_full_day'))
            ->seconds(false)
            ->native(false)
            ->locale('fa')
            ->visible(fn(Get $get) => !$get('is_full_day'))
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.start_time_required'),
                'date' => __('resources/reservation/strings.validation.start_time_date'),
            ]);
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/reservation/strings.fields.status'))
            ->options(ReservationStatus::class)
            ->default('active')
            ->required()
            ->live()
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.status_required'),
                'in' => __('resources/reservation/strings.validation.status_in'),
            ]);
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/reservation/strings.fields.user'))
            ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->required()
            ->validationMessages([
                'required' => __('resources/reservation/strings.validation.user_required'),
                'in' => __('resources/reservation/strings.validation.user_in'),
            ]);
    }
}
