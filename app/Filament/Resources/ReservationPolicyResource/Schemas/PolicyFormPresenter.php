<?php

namespace App\Filament\Resources\ReservationPolicyResource\Schemas;

use App\Enums\ReservationError;
use App\Traits\FilamentFormDivider;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Utilities\Get;

class PolicyFormPresenter
{
    use FilamentFormDivider;
    public static function allowFullDay(): Toggle
    {
        return Toggle::make('allow_full_day')
            ->label(__('resources/policy/strings.fields.allow_full_day'))
            ->helperText(__('resources/policy/strings.hints.allow_full_day'))
            ->onColor('success');
    }

    public static function allowOverlapRelease(): Toggle
    {
        return Toggle::make('allow_overlap_release')
            ->label(__('resources/policy/strings.fields.allow_overlap_release'))
            ->helperText(__('resources/policy/strings.hints.allow_overlap_release'))
            ->onColor('success');
    }

    public static function allowPartialCancel(): Toggle
    {
        return Toggle::make('allow_partial_cancel')
            ->label(__('resources/policy/strings.fields.allow_partial_cancel'))
            ->helperText(__('resources/policy/strings.hints.allow_partial_cancel'))
            ->onColor('success');
    }

    public static function allowRepeat(): Toggle
    {
        return Toggle::make('allow_repeat')
            ->label(__('resources/policy/strings.fields.allow_repeat'))
            ->helperText(__('resources/policy/strings.hints.allow_repeat'))
            ->onColor('success');
    }

    public static function allowedDays(): CheckboxList
    {
        return CheckboxList::make('allowed_days')
            ->label(__('resources/policy/strings.fields.allowed_days'))
            ->helperText(__('resources/policy/strings.hints.allowed_days'))
            ->validationAttribute(__('resources/policy/strings.fields.allowed_days'))
            ->options(__('resources/policy/strings.days'))
            ->columns(4)
            ->columnSpanFull();
    }

    public static function allowedHoursEnd(): TimePicker
    {
        return TimePicker::make('allowed_hours_end')
            ->label(__('resources/policy/strings.fields.allowed_hours_end'))
            ->helperText(__('resources/policy/strings.hints.allowed_hours_end'))
            ->validationAttribute(__('resources/policy/strings.fields.allowed_hours_end'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->after('allowed_hours_start');
    }

    public static function allowedHoursStart(): TimePicker
    {
        return TimePicker::make('allowed_hours_start')
            ->label(__('resources/policy/strings.fields.allowed_hours_start'))
            ->helperText(__('resources/policy/strings.hints.allowed_hours_start'))
            ->validationAttribute(__('resources/policy/strings.fields.allowed_hours_start'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->before('allowed_hours_end');
    }

    public static function errorLegend(): ViewEntry
    {
        return ViewEntry::make('error_legend')
            ->hiddenLabel()
            ->columnSpanFull()
            ->view('filament.resources.policy.legend')
            ->state(fn(): array => ReservationError::legend());
    }

    public static function maxCancelCount(): TextInput
    {
        return TextInput::make('max_cancel_count')
            ->label(__('resources/policy/strings.fields.max_cancel_count'))
            ->helperText(__('resources/policy/strings.hints.max_cancel_count'))
            ->validationAttribute(__('resources/policy/strings.fields.max_cancel_count'))
            ->numeric()
            ->minValue(0)
            ->placeholder('۳');
    }

    public static function cancelWindowDays(): TextInput
    {
        return TextInput::make('cancel_window_days')
            ->label(__('resources/policy/strings.fields.cancel_window_days'))
            ->helperText(__('resources/policy/strings.hints.cancel_window_days'))
            ->validationAttribute(__('resources/policy/strings.fields.cancel_window_days'))
            ->numeric()
            ->integer()
            ->minValue(1)
            ->maxValue(365)
            ->validationMessages([
                'min' => __('resources/policy/strings.validation.cancel_window_days_range'),
                'max' => __('resources/policy/strings.validation.cancel_window_days_range'),
            ])
            ->placeholder('۳۰');
    }

    public static function maxDurationMinutes(): TextInput
    {
        return TextInput::make('max_duration_minutes')
            ->label(__('resources/policy/strings.fields.max_duration_minutes'))
            ->helperText(__('resources/policy/strings.hints.max_duration_minutes'))
            ->validationAttribute(__('resources/policy/strings.fields.max_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۴۸۰');
    }

    public static function maxPerUser(): TextInput
    {
        return TextInput::make('max_per_user')
            ->label(__('resources/policy/strings.fields.max_per_user'))
            ->helperText(__('resources/policy/strings.hints.max_per_user'))
            ->validationAttribute(__('resources/policy/strings.fields.max_per_user'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۱');
    }

    public static function maxRangeDays(): TextInput
    {
        return TextInput::make('max_range_days')
            ->label(__('resources/policy/strings.fields.max_range_days'))
            ->helperText(__('resources/policy/strings.hints.max_range_days'))
            ->validationAttribute(__('resources/policy/strings.fields.max_range_days'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۱۰');
    }

    public static function lastMinuteHours(): TextInput
    {
        return TextInput::make('last_minute_hours')
            ->label(__('resources/policy/strings.fields.last_minute_hours'))
            ->helperText(__('resources/policy/strings.hints.last_minute_hours'))
            ->validationAttribute(__('resources/policy/strings.fields.last_minute_hours'))
            ->numeric()
            ->integer()
            ->minValue(1)
            ->maxValue(720)
            ->rules([fn(Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                if (is_numeric($value) && (int) $value <= (int) $get('window_hours')) {
                    $fail(__('resources/policy/strings.validation.last_minute_hours_gt'));
                }
            }])
            ->validationMessages([
                'min' => __('resources/policy/strings.validation.last_minute_hours_min'),
                'max' => __('resources/policy/strings.validation.last_minute_hours_max'),
            ])
            ->placeholder('۲۴');
    }

    public static function minDurationMinutes(): TextInput
    {
        return TextInput::make('min_duration_minutes')
            ->label(__('resources/policy/strings.fields.min_duration_minutes'))
            ->helperText(__('resources/policy/strings.hints.min_duration_minutes'))
            ->validationAttribute(__('resources/policy/strings.fields.min_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۳۰');
    }

    public static function requiresApproval(): Toggle
    {
        return Toggle::make('requires_approval')
            ->label(__('resources/policy/strings.fields.requires_approval'))
            ->helperText(__('resources/policy/strings.hints.requires_approval'))
            ->disabled()
            ->onColor('warning');
    }

    public static function windowDays(): TextInput
    {
        return TextInput::make('window_days')
            ->label(__('resources/policy/strings.fields.window_days'))
            ->helperText(__('resources/policy/strings.hints.window_days'))
            ->validationAttribute(__('resources/policy/strings.fields.window_days'))
            ->numeric()
            ->minValue(0)
            ->maxValue(365)
            ->placeholder('۲۱');
    }

    public static function windowHours(): TextInput
    {
        return TextInput::make('window_hours')
            ->label(__('resources/policy/strings.fields.window_hours'))
            ->helperText(__('resources/policy/strings.hints.window_hours'))
            ->validationAttribute(__('resources/policy/strings.fields.window_hours'))
            ->numeric()
            ->minValue(0)
            ->maxValue(72)
            ->placeholder('۰');
    }
}
