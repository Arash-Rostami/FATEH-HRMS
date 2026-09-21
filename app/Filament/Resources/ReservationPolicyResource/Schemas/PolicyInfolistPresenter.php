<?php

namespace App\Filament\Resources\ReservationPolicyResource\Schemas;

use App\Models\ReservationPolicy;
use App\Services\Reservation\ValidationService;
use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;

class PolicyInfolistPresenter
{
    use FilamentFormDivider;

    private static function value(ReservationPolicy $record, string $key): mixed
    {
        return app(ValidationService::class)->getPolicies($record->resource_type)[$key] ?? null;
    }

    public static function resourceType(): TextEntry
    {
        return TextEntry::make('resource_type')
            ->label(__('resources/policy/strings.fields.resource_type'))
            ->formatStateUsing(fn(ReservationPolicy $record) => \App\Enums\ResourceType::tryFrom($record->resource_type)?->getLabel() ?? $record->resource_type)
            ->icon('heroicon-o-tag')
            ->badge()
            ->color('primary');
    }

    public static function windowDays(): TextEntry
    {
        return TextEntry::make('window_days')
            ->label(__('resources/policy/strings.fields.window_days'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'window_days'))
            ->icon('heroicon-o-calendar')
            ->placeholder('-');
    }

    public static function windowHours(): TextEntry
    {
        return TextEntry::make('window_hours')
            ->label(__('resources/policy/strings.fields.window_hours'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'window_hours'))
            ->icon('heroicon-o-clock')
            ->placeholder('-');
    }

    public static function minDurationMinutes(): TextEntry
    {
        return TextEntry::make('min_duration_minutes')
            ->label(__('resources/policy/strings.fields.min_duration_minutes'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'min_duration_minutes'))
            ->icon('heroicon-o-arrow-trending-down')
            ->placeholder('-');
    }

    public static function maxDurationMinutes(): TextEntry
    {
        return TextEntry::make('max_duration_minutes')
            ->label(__('resources/policy/strings.fields.max_duration_minutes'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'max_duration_minutes'))
            ->icon('heroicon-o-arrow-trending-up')
            ->placeholder('-');
    }

    public static function allowedHours(): TextEntry
    {
        return TextEntry::make('allowed_hours')
            ->label(__('resources/policy/strings.fields.allowed_hours_start') . ' - ' . __('resources/policy/strings.fields.allowed_hours_end'))
            ->getStateUsing(function (ReservationPolicy $record): string {
                $hours = self::value($record, 'allowed_hours');
                $start = $hours['start'] ?? null;
                $end = $hours['end'] ?? null;

                return ($start && $end) ? "{$start} - {$end}" : '-';
            })
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-clock')
            ->placeholder('-');
    }

    public static function allowedDays(): TextEntry
    {
        return TextEntry::make('allowed_days')
            ->label(__('resources/policy/strings.fields.allowed_days'))
            ->getStateUsing(fn(ReservationPolicy $record) => collect(self::value($record, 'allowed_days') ?? [])
                ->map(fn($day) => __("resources/policy/strings.days.{$day}"))
                ->implode('، '))
            ->icon('heroicon-o-calendar-days')
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function maxPerUser(): TextEntry
    {
        return TextEntry::make('max_per_user')
            ->label(__('resources/policy/strings.fields.max_per_user'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'max_per_user'))
            ->badge()
            ->color('info')
            ->placeholder('-');
    }

    public static function maxRangeDays(): TextEntry
    {
        return TextEntry::make('max_range_days')
            ->label(__('resources/policy/strings.fields.max_range_days'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'max_range_days'))
            ->badge()
            ->color('info')
            ->placeholder('-');
    }

    public static function maxCancelCount(): TextEntry
    {
        return TextEntry::make('max_cancel_count')
            ->label(__('resources/policy/strings.fields.max_cancel_count'))
            ->getStateUsing(fn(ReservationPolicy $record) => self::value($record, 'max_cancel_count'))
            ->badge()
            ->color('info')
            ->placeholder('-');
    }

    public static function allowFullDay(): IconEntry
    {
        return IconEntry::make('allow_full_day')
            ->label(__('resources/policy/strings.fields.allow_full_day'))
            ->getStateUsing(fn(ReservationPolicy $record) => (bool) self::value($record, 'allow_full_day'))
            ->boolean();
    }

    public static function allowRepeat(): IconEntry
    {
        return IconEntry::make('allow_repeat')
            ->label(__('resources/policy/strings.fields.allow_repeat'))
            ->getStateUsing(fn(ReservationPolicy $record) => (bool) self::value($record, 'allow_repeat'))
            ->boolean();
    }

    public static function allowPartialCancel(): IconEntry
    {
        return IconEntry::make('allow_partial_cancel')
            ->label(__('resources/policy/strings.fields.allow_partial_cancel'))
            ->getStateUsing(fn(ReservationPolicy $record) => (bool) self::value($record, 'allow_partial_cancel'))
            ->boolean();
    }

    public static function allowOverlapRelease(): IconEntry
    {
        return IconEntry::make('allow_overlap_release')
            ->label(__('resources/policy/strings.fields.allow_overlap_release'))
            ->getStateUsing(fn(ReservationPolicy $record) => (bool) self::value($record, 'allow_overlap_release'))
            ->boolean();
    }

    public static function requiresApproval(): IconEntry
    {
        return IconEntry::make('requires_approval')
            ->label(__('resources/policy/strings.fields.requires_approval'))
            ->getStateUsing(fn(ReservationPolicy $record) => (bool) self::value($record, 'requires_approval'))
            ->boolean();
    }

    public static function rulesCount(): TextEntry
    {
        return TextEntry::make('rules_count')
            ->label(__('resources/policy/strings.fields.rules_count'))
            ->icon('heroicon-o-list-bullet')
            ->badge()
            ->color('info');
    }

    public static function lastUpdated(): TextEntry
    {
        return TextEntry::make('last_updated')
            ->label('آخرین ویرایش')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->color('gray')
            ->icon('heroicon-o-clock')
            ->placeholder('-');
    }
}
