<?php

namespace App\Filament\Resources\DepartmentResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class DepartmentInfolistPresenter
{
    public static function code(): TextEntry
    {
        return TextEntry::make('code')
            ->label(__('resources/department/strings.fields.code'))
            ->badge()
            ->color('primary')
            ->fontFamily(FontFamily::Mono)
            ->copyable()
            ->copyMessageDuration(1500);
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/department/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/department/strings.fields.description'))
            ->columnSpanFull()
            ->placeholder('-');
    }

    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/department/strings.fields.name'))
            ->size(TextSize::Small)
            ->weight(FontWeight::Bold);
    }

    public static function sections(): TextEntry
    {
        return TextEntry::make('sections')
            ->label(__('resources/department/strings.fields.sections'))
            ->badge()
            ->placeholder('-');
    }

    public static function units(): TextEntry
    {
        return TextEntry::make('units')
            ->label(__('resources/department/strings.fields.units'))
            ->badge()
            ->placeholder('-');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/department/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function usersCount(): TextEntry
    {
        return TextEntry::make('users_count')
            ->label(__('resources/department/strings.fields.users_count'))
            ->state(fn($record): int => $record->users_count ?? $record->users()->count())
            ->badge()
            ->color('info')
            ->icon('heroicon-m-users');
    }
}
