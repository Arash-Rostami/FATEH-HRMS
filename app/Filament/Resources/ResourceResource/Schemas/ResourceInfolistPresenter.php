<?php

namespace App\Filament\Resources\ResourceResource\Schemas;

use App\Enums\ResourceType;
use App\Filament\Resources\ResourceResource\Enums\ResourceStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class ResourceInfolistPresenter
{
    public static function capacity(): TextEntry
    {
        return TextEntry::make('metadata.capacity')
            ->label(__('resources/resource/strings.fields.capacity'))
            ->placeholder('—');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/resource/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')->placeholder('-');
    }

    public static function extension(): TextEntry
    {
        return TextEntry::make('metadata.extension')
            ->label(__('resources/resource/strings.fields.extension'))
            ->placeholder('—');
    }

    public static function floor(): TextEntry
    {
        return TextEntry::make('metadata.floor')
            ->label(__('resources/resource/strings.fields.floor'))
            ->placeholder('—');
    }

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/resource/strings.fields.name'))
            ->weight(FontWeight::Bold)->size(TextSize::Large);
    }

    public static function notes(): TextEntry
    {
        return TextEntry::make('metadata.notes')
            ->label(__('resources/resource/strings.fields.notes'))
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
            ->color(fn(string $state) => ResourceStatus::tryFrom($state)?->getColor() ?? 'gray');
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
            ->color('gray')->placeholder('-');
    }
}
