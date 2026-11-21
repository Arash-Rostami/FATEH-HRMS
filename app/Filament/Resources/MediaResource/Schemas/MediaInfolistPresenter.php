<?php

namespace App\Filament\Resources\MediaResource\Schemas;

use App\Filament\Resources\ReportResource\Schemas\ReportInfolistPresenter;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;

class MediaInfolistPresenter extends ReportInfolistPresenter
{
    public static function active(): IconEntry
    {
        return parent::active()->label(__('resources/media/strings.fields.active'));
    }

    public static function cover(): ImageEntry
    {
        return ImageEntry::make('cover_url')
            ->label(__('resources/media/strings.fields.cover_image'))
            ->imageHeight(100)
            ->circular(false)
            ->columnSpanFull();
    }

    public static function duration(): TextEntry
    {
        return TextEntry::make('duration_seconds')
            ->label(__('resources/media/strings.table.duration'))
            ->formatStateUsing(fn($state): string => MediaTablePresenter::durationLabel($state))
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray');
    }

    public static function kind(): TextEntry
    {
        return TextEntry::make('kind')
            ->label(__('resources/media/strings.fields.kind'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => MediaTablePresenter::kindLabel($state))
            ->color(fn(string $state): string => match ($state) {
                'series' => 'info',
                'video' => 'warning',
                default => 'success',
            })
            ->icon(fn(string $state): string => match ($state) {
                'series' => 'heroicon-o-rectangle-stack',
                'video' => 'heroicon-o-video-camera',
                default => 'heroicon-o-speaker-wave',
            });
    }

    public static function pinned(): IconEntry
    {
        return parent::pinned()->label(__('resources/media/strings.fields.pinned'));
    }

    public static function reportDate(): TextEntry
    {
        return parent::reportDate()->label(__('resources/media/strings.fields.report_date'));
    }

    public static function title(): TextEntry
    {
        return parent::title()->label(__('resources/media/strings.fields.title'));
    }
}
