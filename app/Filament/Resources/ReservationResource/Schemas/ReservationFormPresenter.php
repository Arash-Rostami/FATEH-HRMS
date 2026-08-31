<?php

namespace App\Filament\Resources\ReservationResource\Schemas;

use App\Enums\CancelReason;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Traits\FilamentFormDivider;
use App\Services\PersianDateFieldService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class ReservationFormPresenter
{
    use FilamentFormDivider;

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
            ->helperText(__('resources/reservation/strings.hints.cancel_reason'));
    }

    public static function endDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'end_time_date',
            label: __('resources/reservation/strings.fields.end_time'),
            required: true,
            yearFrom: 1400,
            yearTo: Jalalian::now()->getYear() + 10,
            fullWidth: true,
        )
            ->visible(fn(Get $get) => !$get('is_full_day'))
            ->helperText(__('resources/reservation/strings.hints.end_time'));
    }

    public static function endTimePart(): TextInput
    {
        return TextInput::make('end_time_time')
            ->label(__('resources/reservation/strings.fields.end_time_time'))
            ->type('time')
            ->default('17:00')
            ->nullable()
            ->visible(fn(Get $get) => !$get('is_full_day'))
            ->columnSpan(2)
            ->helperText(__('resources/reservation/strings.hints.end_time'));
    }

    public static function isFullDay(): Toggle
    {
        return Toggle::make('is_full_day')
            ->label(__('resources/reservation/strings.fields.is_full_day'))
            ->onColor('success')
            ->columnSpanFull()
            ->helperText(__('resources/reservation/strings.hints.is_full_day'))
            ->disabled(fn(Get $get) => self::isRangeState($get))
            ->dehydrated()
            ->live();
    }

    public static function isRecurring(): Toggle
    {
        return Toggle::make('is_recurring')
            ->label(__('resources/reservation/strings.fields.is_recurring'))
            ->dehydrated(false)
            ->afterStateUpdated(fn(Set $set, bool $state) => $state && $set('recur_pattern', 'daily'))
            ->disabled(fn(Get $get) => self::isRangeState($get))
            ->live()
            ->columnSpanFull()
            ->helperText(__('resources/reservation/strings.hints.is_recurring'))
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
            ->placeholder('—');
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
            ->disabled(fn(Get $get) => self::isRangeState($get))
            ->visible(fn(Get $get) => (bool)$get('is_recurring') && !self::isRangeState($get))
            ->required(fn(Get $get) => (bool)$get('is_recurring'))
            ->helperText(__('resources/reservation/strings.hints.recur_count'));
    }

    public static function recurPattern(): Select
    {
        return Select::make('recur_pattern')
            ->label(__('resources/reservation/strings.fields.recur_pattern'))
            ->options(__('resources/reservation/strings.recur_patterns'))
            ->native(false)
            ->default('daily')
            ->dehydrated(false)
            ->disabled(fn(Get $get) => self::isRangeState($get))
            ->visible(fn(Get $get) => (bool)$get('is_recurring') && !self::isRangeState($get))
            ->required(fn(Get $get) => (bool)$get('is_recurring'))
            ->helperText(__('resources/reservation/strings.hints.recur_pattern'));
    }

    private static function isRangeState(Get $get): bool
    {
        if ($get('is_full_day')) {
            return false;
        }
        $start = $get('start_time_date');
        $end = $get('end_time_date');
        if (!$start || !$end) {
            return false;
        }
        try {
            return Carbon::parse($start)->diffInDays(Carbon::parse($end)) >= 1;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function resourceId(): Select
    {
        return Select::make('resource_id')
            ->label(__('resources/reservation/strings.fields.resource'))
            ->options(fn() => Resource::orderBy('name')->get()->pluck('labeled_name', 'id'))
            ->searchable()
            ->preload()
            ->required()
            ->helperText(__('resources/reservation/strings.hints.resource_id'));
    }

    public static function startDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'start_time_date',
            label: __('resources/reservation/strings.fields.start_time'),
            required: true,
            yearFrom: 1400,
            yearTo: Jalalian::now()->getYear() + 10,
            fullWidth: true,
        )
            ->helperText(__('resources/reservation/strings.hints.start_time'));
    }

    public static function startTimePart(): TextInput
    {
        return TextInput::make('start_time_time')
            ->label(__('resources/reservation/strings.fields.start_time_time'))
            ->type('time')
            ->default('09:00')
            ->nullable()
            ->visible(fn(Get $get) => !$get('is_full_day'))
            ->columnSpan(2)
            ->helperText(__('resources/reservation/strings.hints.start_time'));
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/reservation/strings.fields.status'))
            ->options(ReservationStatus::class)
            ->default('active')
            ->required()
            ->live()
            ->helperText(__('resources/reservation/strings.hints.status'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/reservation/strings.fields.user'))
            ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->required()
            ->helperText(__('resources/reservation/strings.hints.user_id'));
    }
}
