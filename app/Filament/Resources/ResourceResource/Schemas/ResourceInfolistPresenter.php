<?php

namespace App\Filament\Resources\ResourceResource\Schemas;

use App\Enums\ResourceType;
use App\Filament\Resources\ResourceResource\Enums\ResourceStatus;
use App\Traits\FilamentFormDivider;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class ResourceInfolistPresenter
{
    use FilamentFormDivider;

    public static function availableDays(): TextEntry
    {
        return TextEntry::make('metadata.available_days')
            ->label(__('resources/resource/strings.fields.available_days'))
            ->formatStateUsing(fn($state) => is_array($state)
                ? collect($state)->map(fn($day) => __("resources/policy/strings.days.{$day}"))->implode('، ')
                : $state)
            ->icon('heroicon-o-calendar-days')
            ->placeholder('—');
    }

    public static function capacity(): TextEntry
    {
        return TextEntry::make('metadata.capacity')
            ->label(__('resources/resource/strings.fields.capacity'))
            ->icon('heroicon-o-user-group')
            ->placeholder('—');
    }

    public static function cardNumber(): TextEntry
    {
        return TextEntry::make('metadata.card')
            ->label(__('resources/resource/strings.fields.card_number'))
            ->icon('heroicon-o-identification')
            ->placeholder('—');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/resource/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray')->placeholder('-');
    }

    public static function extension(): TextEntry
    {
        return TextEntry::make('metadata.extension')
            ->label(__('resources/resource/strings.fields.extension'))
            ->icon('heroicon-o-phone')
            ->placeholder('—');
    }

    public static function floor(): TextEntry
    {
        return TextEntry::make('metadata.floor')
            ->label(__('resources/resource/strings.fields.floor'))
            ->icon('heroicon-o-building-office')
            ->placeholder('—');
    }

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/resource/strings.fields.name'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->weight(FontWeight::Bold)->size(TextSize::Large)
            ->copyable();
    }

    public static function notes(): TextEntry
    {
        return TextEntry::make('metadata.notes')
            ->label(__('resources/resource/strings.fields.notes'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->icon('heroicon-o-document-text')
            ->columnSpanFull()->placeholder('—');
    }

    public static function reservationsCount(): TextEntry
    {
        return TextEntry::make('reservations_count')
            ->label(__('resources/resource/strings.fields.reservations_count'))
            ->badge()->color('info')
            ->icon('heroicon-m-calendar-days')
            ->state(fn($record): int => $record->reservations_count ?? $record->reservations()->count());
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/resource/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ResourceStatus::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ResourceStatus::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state) => ResourceStatus::tryFrom($state)?->getIcon() ?? null);
    }

    public static function timeSlots(): TextEntry
    {
        return TextEntry::make('metadata.time_slots')
            ->label(__('resources/resource/strings.fields.time_slot_start') . ' - ' . __('resources/resource/strings.fields.time_slot_end'))
            ->state(fn($record) => isset($record->metadata['time_slots']['start'], $record->metadata['time_slots']['end'])
                ? $record->metadata['time_slots']['start'] . ' - ' . $record->metadata['time_slots']['end']
                : null)
            ->icon('heroicon-o-clock')
            ->placeholder('—');
    }

    public static function unit(): TextEntry
    {
        return TextEntry::make('metadata.unit')
            ->label(__('resources/resource/strings.fields.unit'))
            ->icon('heroicon-o-cube')
            ->placeholder('—');
    }

    public static function type(): TextEntry
    {
        return TextEntry::make('type')
            ->label(__('resources/resource/strings.fields.type'))
            ->badge()
            ->formatStateUsing(fn(string $state) => ResourceType::tryFrom($state)?->getLabel() ?? $state)
            ->color(fn(string $state) => ResourceType::tryFrom($state)?->getColor() ?? 'gray')
            ->icon(fn(string $state) => ResourceType::tryFrom($state)?->getIcon() ?? null);
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/resource/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-arrow-path')
            ->color('gray')->placeholder('-');
    }
}
