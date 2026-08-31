<?php

namespace App\Filament\Resources\AdResource\Schemas;

use App\Filament\Resources\AdResource\Enums\AdGender;
use App\Filament\Resources\AdResource\Enums\AdStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class AdInfolistPresenter
{
    public static function active(): TextEntry
    {
        return TextEntry::make('active')
            ->label(__('resources/ad/strings.fields.active'))
            ->badge()
            ->formatStateUsing(fn(bool $state): string => AdStatus::fromBool($state)->getLabel())
            ->color(fn(bool $state): string => AdStatus::fromBool($state)->getColor())
            ->icon(fn(bool $state): string => AdStatus::fromBool($state)->getIcon());
    }

    public static function certificate(): TextEntry
    {
        return TextEntry::make('certificate')
            ->label(__('resources/ad/strings.fields.certificate'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/ad/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function experience(): TextEntry
    {
        return TextEntry::make('experience')
            ->label(__('resources/ad/strings.fields.experience'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function extra(): RepeatableEntry
    {
        return RepeatableEntry::make('extra')
            ->label(__('resources/ad/strings.fields.extra'))
            ->schema([
                TextEntry::make('key')
                    ->label(__('resources/ad/strings.fields.extra_key'))
                    ->weight('bold'),
                TextEntry::make('value')
                    ->label(__('resources/ad/strings.fields.extra_value')),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    public static function gender(): TextEntry
    {
        return TextEntry::make('gender')
            ->label(__('resources/ad/strings.fields.gender'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => AdGender::from($state)->getLabel())
            ->color(fn(string $state): string => AdGender::from($state)->getColor())
            ->icon(fn(string $state): string => AdGender::from($state)->getIcon());
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label('ID')
            ->color('gray');
    }

    public static function link(): TextEntry
    {
        return TextEntry::make('link')
            ->label(__('resources/ad/strings.fields.link'))
            ->url(fn($record) => $record->link)
            ->openUrlInNewTab()
            ->placeholder('-');
    }

    public static function position(): TextEntry
    {
        return TextEntry::make('position')
            ->label(__('resources/ad/strings.fields.position'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;']);
    }

    public static function skill(): TextEntry
    {
        return TextEntry::make('skill')
            ->label(__('resources/ad/strings.fields.skill'))
            ->placeholder('-')
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/ad/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }
}
