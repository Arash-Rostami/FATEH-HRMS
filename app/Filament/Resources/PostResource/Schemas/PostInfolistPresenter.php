<?php

namespace App\Filament\Resources\PostResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class PostInfolistPresenter
{
    public static function body(): TextEntry
    {
        return TextEntry::make('body')
            ->label(__('resources/post/strings.fields.body'))
            ->html()
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/post/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function image(): ImageEntry
    {
        return ImageEntry::make('image')
            ->label(__('resources/post/strings.fields.image'))
            ->disk('public')
            ->imageHeight(200)
            ->columnSpanFull()
            ->hidden(fn ($record) => ! $record || blank($record->image) || $record->image === 'NULL' || $record->image == '');
    }

    public static function pinned(): IconEntry
    {
        return IconEntry::make('pinned')
            ->label(__('resources/post/strings.fields.pinned'))
            ->boolean()
            ->trueIcon('heroicon-o-bookmark-slash')
            ->falseIcon('heroicon-o-bookmark')
            ->trueColor('warning')
            ->falseColor('gray');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/post/strings.fields.title'))
            ->html()
            ->size(TextSize::Large)
            ->weight(FontWeight::Bold)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/post/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/post/strings.fields.user'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }
}
