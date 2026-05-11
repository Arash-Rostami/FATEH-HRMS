<?php

namespace App\Filament\Resources\LinkResource\Schemas;

use App\Filament\Resources\LinkResource\Enums\LinkType;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class LinkInfolistPresenter
{
    public static function companyIps(): TextEntry
    {
        return TextEntry::make('extra')
            ->label(__('resources/link/strings.fields.extra'))
            ->getStateUsing(fn($record) => implode('، ', $record->extra ?? []))
            ->icon('heroicon-o-cpu-chip')
            ->hidden(fn($record) => $record === null || $record->isExtraEmptyInDatabase())
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/link/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function icon(): ImageEntry
    {
        return ImageEntry::make('icon')
            ->label(__('resources/link/strings.fields.icon'))
            ->disk('public')
            ->imageHeight(48);
    }

    public static function iconDescription(): TextEntry
    {
        return TextEntry::make('icon_description')
            ->label(__('resources/link/strings.fields.icon_description'))
            ->html()
            ->placeholder('—')
            ->columnSpan(3)
            ->color('gray');
    }

    public static function image(): ImageEntry
    {
        return ImageEntry::make('image')
            ->label(__('resources/link/strings.fields.image'))
            ->disk('public')
            ->columnSpanFull()
            ->imageHeight(120);
    }

    public static function imageDescription(): TextEntry
    {
        return TextEntry::make('image_description')
            ->label(__('resources/link/strings.fields.image_description'))
            ->placeholder('—')
            ->columnSpan(1)
            ->color('gray');
    }

    public static function internalUrl(): TextEntry
    {
        return TextEntry::make('internal_url')
            ->label(__('resources/link/strings.fields.internal_url'))
            ->url(fn($state) => $state, true)
            ->icon('heroicon-o-building-office')
            ->hidden(fn($record) => blank($record?->internal_url))
            ->columnSpanFull();
    }

    public static function linkType(): TextEntry
    {
        return TextEntry::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->getStateUsing(fn($record) => LinkType::tryFrom($record->link))
            ->badge();
    }

    public static function sequence(): TextEntry
    {
        return TextEntry::make('sequence')
            ->label(__('resources/link/strings.fields.sequence'))
            ->badge()
            ->color('gray');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/link/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function url(): TextEntry
    {
        return TextEntry::make('url')
            ->label(__('resources/link/strings.fields.url'))
            ->url(fn($state) => $state, true)
            ->icon('heroicon-o-globe-alt')
            ->columnSpanFull();
    }

    public static function urlDescription(): TextEntry
    {
        return TextEntry::make('url_description')
            ->label(__('resources/link/strings.fields.url_description'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function urlTitle(): TextEntry
    {
        return TextEntry::make('url_title')
            ->label(__('resources/link/strings.fields.url_title'))
            ->size(TextSize::Large)
            ->weight(FontWeight::Bold)
            ->columnSpanFull();
    }
}
