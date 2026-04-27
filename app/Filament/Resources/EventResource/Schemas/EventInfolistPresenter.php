<?php

namespace App\Filament\Resources\EventResource\Schemas;

use Filament\Support\Enums\IconPosition;
use Filament\Infolists\Components\{IconEntry, TextEntry};
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class EventInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/event/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function date(): TextEntry
    {
        return TextEntry::make('date')
            ->label(__('resources/event/strings.fields.date'))
            ->formatStateUsing(fn ($state) => toJalaliSmart($state))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->iconPosition(IconPosition::After)
            ->alignEnd()
            ->icon('heroicon-o-calendar');
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/event/strings.fields.description'))
            ->placeholder('—')
            ->extraAttributes(['style' => 'margin-bottom:15px'])
            ->columnSpanFull();
    }

    public static function private(): IconEntry
    {
        return IconEntry::make('private')
            ->label(__('resources/event/strings.fields.private'))
            ->boolean()
            ->trueIcon('heroicon-o-lock-closed')
            ->falseIcon('heroicon-o-globe-alt')
            ->trueColor('warning')
            ->falseColor('success');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/event/strings.fields.title'))
            ->size(TextSize::Large)
            ->weight(FontWeight::Bold)
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/event/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/event/strings.fields.user'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }
}
