<?php

namespace App\Filament\Resources\ReservationPolicyResource\Schemas;

use App\Enums\ReservationError;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ViewEntry;

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
            ->columnSpanFull()
            ->validationMessages([
                'array' => __('resources/policy/strings.validation.invalid_array'),
                'in' => __('resources/reservationpolicy/strings.validation.allowed_days.in')
            ]);
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
            ->after('allowed_hours_start')
            ->validationMessages([
                'after' => __('resources/policy/strings.validation.time_after'),
                'date_format' => __('resources/policy/strings.validation.time_format'),
            ]);
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
            ->before('allowed_hours_end')
            ->validationMessages([
                'before' => __('resources/policy/strings.validation.time_before'),
                'date_format' => __('resources/policy/strings.validation.time_format'),
            ]);
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
            ->placeholder('۳')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_count'),
            ]);
    }

    public static function maxDurationMinutes(): TextInput
    {
        return TextInput::make('max_duration_minutes')
            ->label(__('resources/policy/strings.fields.max_duration_minutes'))
            ->helperText(__('resources/policy/strings.hints.max_duration_minutes'))
            ->validationAttribute(__('resources/policy/strings.fields.max_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۴۸۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_minutes'),
            ]);
    }

    public static function maxPerUser(): TextInput
    {
        return TextInput::make('max_per_user')
            ->label(__('resources/policy/strings.fields.max_per_user'))
            ->helperText(__('resources/policy/strings.hints.max_per_user'))
            ->validationAttribute(__('resources/policy/strings.fields.max_per_user'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۱')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_days'),
            ]);
    }

    public static function minDurationMinutes(): TextInput
    {
        return TextInput::make('min_duration_minutes')
            ->label(__('resources/policy/strings.fields.min_duration_minutes'))
            ->helperText(__('resources/policy/strings.hints.min_duration_minutes'))
            ->validationAttribute(__('resources/policy/strings.fields.min_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۳۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_minutes'),
            ]);
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
            ->minValue(1)
            ->maxValue(365)
            ->placeholder('۲۱')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_days'),
                'max' => __('resources/policy/strings.validation.max_days'),
            ]);
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
            ->placeholder('۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.numeric'),
                'min' => __('resources/policy/strings.validation.min_hours'),
                'max' => __('resources/policy/strings.validation.max_hours'),
            ]);
    }
}
