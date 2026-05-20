<?php

namespace App\Filament\Resources\ReservationPolicyResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\HtmlString;

class PolicyFormPresenter
{
    public static function windowDays(): TextInput
    {
        return TextInput::make('window_days')
            ->label(__('resources/policy/strings.fields.window_days'))
            ->numeric()
            ->minValue(1)
            ->maxValue(365)
            ->placeholder('۲۱')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.window_days_numeric'),
                'min'     => __('resources/policy/strings.validation.window_days_min'),
                'max'     => __('resources/policy/strings.validation.window_days_max'),
            ]);
    }

    public static function windowHours(): TextInput
    {
        return TextInput::make('window_hours')
            ->label(__('resources/policy/strings.fields.window_hours'))
            ->numeric()
            ->minValue(0)
            ->maxValue(72)
            ->placeholder('۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.window_hours_numeric'),
                'min'     => __('resources/policy/strings.validation.window_hours_min'),
                'max'     => __('resources/policy/strings.validation.window_hours_max'),
            ]);
    }

    public static function maxPerUser(): TextInput
    {
        return TextInput::make('max_per_user')
            ->label(__('resources/policy/strings.fields.max_per_user'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۱')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.max_per_user_numeric'),
                'min'     => __('resources/policy/strings.validation.max_per_user_min'),
            ]);
    }

    public static function maxCancelCount(): TextInput
    {
        return TextInput::make('max_cancel_count')
            ->label(__('resources/policy/strings.fields.max_cancel_count'))
            ->numeric()
            ->minValue(0)
            ->placeholder('۳')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.max_cancel_count_numeric'),
                'min'     => __('resources/policy/strings.validation.max_cancel_count_min'),
            ]);
    }

    public static function minDurationMinutes(): TextInput
    {
        return TextInput::make('min_duration_minutes')
            ->label(__('resources/policy/strings.fields.min_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۳۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.min_duration_minutes_numeric'),
                'min'     => __('resources/policy/strings.validation.min_duration_minutes_min'),
            ]);
    }

    public static function maxDurationMinutes(): TextInput
    {
        return TextInput::make('max_duration_minutes')
            ->label(__('resources/policy/strings.fields.max_duration_minutes'))
            ->numeric()
            ->minValue(1)
            ->placeholder('۴۸۰')
            ->validationMessages([
                'numeric' => __('resources/policy/strings.validation.max_duration_minutes_numeric'),
                'min'     => __('resources/policy/strings.validation.max_duration_minutes_min'),
            ]);
    }

    public static function allowedHoursStart(): TimePicker
    {
        return TimePicker::make('allowed_hours_start')
            ->label(__('resources/policy/strings.fields.allowed_hours_start'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->before('allowed_hours_end')
            ->validationMessages([
                'before'      => __('resources/policy/strings.validation.allowed_hours_start_before'),
                'date_format' => __('resources/policy/strings.validation.allowed_hours_start_format'),
            ]);
    }

    public static function allowedHoursEnd(): TimePicker
    {
        return TimePicker::make('allowed_hours_end')
            ->label(__('resources/policy/strings.fields.allowed_hours_end'))
            ->seconds(false)
            ->displayFormat('H:i')
            ->format('H:i')
            ->after('allowed_hours_start')
            ->validationMessages([
                'after'       => __('resources/policy/strings.validation.allowed_hours_end_after'),
                'date_format' => __('resources/policy/strings.validation.allowed_hours_end_format'),
            ]);
    }

    public static function allowedDays(): CheckboxList
    {
        return CheckboxList::make('allowed_days')
            ->label(__('resources/policy/strings.fields.allowed_days'))
            ->options(__('resources/policy/strings.days'))
            ->columns(4)
            ->columnSpanFull()
            ->validationMessages([
                'array' => __('resources/policy/strings.validation.allowed_days_array'),
            ]);
    }

    public static function allowFullDay(): Toggle
    {
        return Toggle::make('allow_full_day')
            ->label(__('resources/policy/strings.fields.allow_full_day'))
            ->onColor('success');
    }

    public static function divider(): TextEntry
    {
        return TextEntry::make('divider')
            ->hiddenLabel()
            ->columnSpanFull()
            ->state(new HtmlString('<div class="w-2/3 h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-700 to-transparent opacity-80 mx-auto"></div>'));
    }

    public static function allowRepeat(): Toggle
    {
        return Toggle::make('allow_repeat')
            ->label(__('resources/policy/strings.fields.allow_repeat'))
            ->onColor('success');
    }

    public static function allowPartialCancel(): Toggle
    {
        return Toggle::make('allow_partial_cancel')
            ->label(__('resources/policy/strings.fields.allow_partial_cancel'))
            ->onColor('success');
    }

    public static function allowOverlapRelease(): Toggle
    {
        return Toggle::make('allow_overlap_release')
            ->label(__('resources/policy/strings.fields.allow_overlap_release'))
            ->onColor('success');
    }

    public static function requiresApproval(): Toggle
    {
        return Toggle::make('requires_approval')
            ->label(__('resources/policy/strings.fields.requires_approval'))
            ->onColor('warning');
    }
}
