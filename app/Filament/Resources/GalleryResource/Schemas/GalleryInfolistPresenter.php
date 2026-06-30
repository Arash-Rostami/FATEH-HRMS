<?php

namespace App\Filament\Resources\GalleryResource\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class GalleryInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/gallery/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.description')
            ->label(__('resources/gallery/strings.fields.department'))
            ->placeholder(__('resources/gallery/strings.fields.public_gallery'))
            ->badge()
            ->getStateUsing(function ($record) {
                $models = $record->all_department_models;
                if ($models->isEmpty()) return null;
                return $models->map(fn($d) => $d->displayLabel())->join(', ');
            })
            ->tooltip(function ($record) {
                $models = $record->all_department_models;
                if ($models->isEmpty()) return null;
                return $models->map(fn($d) => $d->tooltipLabel())->join(', ');
            })
            ->color(fn($state) => $state ? 'warning' : 'success')
            ->icon(fn($record) => count($record->all_departments) > 1 ? 'heroicon-o-users' : (count($record->all_departments) === 1 ? 'heroicon-o-lock-closed' : 'heroicon-o-globe-alt'));
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/gallery/strings.fields.description'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function eventDate(): TextEntry
    {
        return TextEntry::make('event_date')
            ->label(__('resources/gallery/strings.fields.event_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->placeholder('—')
            ->icon('heroicon-o-calendar');
    }

    public static function photos(): ImageEntry
    {
        return ImageEntry::make('path')
            ->label(__('resources/gallery/strings.fields.photos'))
            ->disk('public')
            ->imageHeight(160)
            ->columnSpanFull()
            ->stacked()
            ->limit(12)
            ->limitedRemainingText();
    }

    public static function photosCount(): TextEntry
    {
        return TextEntry::make('photos_count')
            ->label(__('resources/gallery/strings.fields.count'))
            ->getStateUsing(fn($record) => count($record->path ?? []))
            ->badge()
            ->columnSpan(2)
            ->color('primary')
            ->icon('heroicon-o-photo');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/gallery/strings.fields.title'))
            ->size(TextSize::Medium)
            ->weight(FontWeight::Bold)
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/gallery/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
