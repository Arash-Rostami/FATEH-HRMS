<?php

namespace App\Filament\Resources\FAQResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

class FAQInfolistPresenter
{
    public static function answer(): TextEntry
    {
        return TextEntry::make('answer')
            ->label(__('resources/faq/strings.fields.answer'))
            ->html()
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function category(): TextEntry
    {
        return TextEntry::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->badge()
            ->color('info')
            ->placeholder('-');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/faq/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.name')
            ->label(__('resources/faq/strings.fields.department'))
            ->formatStateUsing(fn(?Model $record): string => $record?->department->description ?? $record?->department->name ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department->name ?? $record?->department->description ?? '-')

            ->placeholder('-');
    }

    public static function id(): TextEntry
    {
        return TextEntry::make('id')
            ->label('ID')
            ->color('gray');
    }

    public static function question(): TextEntry
    {
        return TextEntry::make('question')
            ->label(__('resources/faq/strings.fields.question'))
            ->html()
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/faq/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/faq/strings.fields.user'))
            ->placeholder('-');
    }
}
