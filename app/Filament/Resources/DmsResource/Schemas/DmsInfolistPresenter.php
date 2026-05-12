<?php


namespace App\Filament\Resources\DmsResource\Schemas;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class DmsInfolistPresenter
{
    public static function code(): TextEntry
    {
        return TextEntry::make('code')
            ->label(__('resources/dms/strings.fields.code'))
            ->icon('heroicon-o-hashtag')
            ->copyable();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/dms/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-clock');
    }


    public static function extra(): KeyValueEntry
    {
        return KeyValueEntry::make('extra')
            ->label(__('resources/dms/strings.fields.extra'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->getStateUsing(fn($record) => collect($record->extra ?? [])
                ->except(['users'])
                ->filter()
                ->toArray()
                ?: null
            )
            ->columnSpanFull();
    }

    public static function file(): TextEntry
    {
        return TextEntry::make('file')
            ->label(__('resources/dms/strings.fields.file'))
            ->icon('heroicon-o-paper-clip')
            ->url(fn($record) => $record->file ? asset('storage/' . $record->file) : null)
            ->openUrlInNewTab()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function owners(): TextEntry
    {
        return TextEntry::make('owners_names')
            ->label(__('resources/dms/strings.fields.owners'))
            ->getStateUsing(fn($record) => $record->getDepartmentDescriptions() ?? $record->getDepartmentNames() ?: '—')
            ->icon('heroicon-o-building-office')
            ->columnSpanFull();
    }

    public static function ownersPreview(): TextEntry
    {
        return TextEntry::make('extra.users')
            ->label(__('resources/dms/strings.fields.owners_preview'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function readCount(): TextEntry
    {
        return TextEntry::make('combined_read_count')
            ->label(__('resources/dms/strings.fields.read_count'))
            ->badge()
            ->color('success')
            ->default('0')
            ->icon('heroicon-o-eye');
    }

    public static function reads(): RepeatableEntry
    {
        return RepeatableEntry::make('reads')
            ->label(__('resources/dms/strings.infolist.section_reads'))
            ->schema([
                TextEntry::make('user.name')
                    ->label(__('resources/dms/strings.fields.reader'))
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                TextEntry::make('read_count')
                    ->label(__('resources/dms/strings.fields.read_count'))
                    ->badge()
                    ->color('success'),

                TextEntry::make('created_at')
                    ->label(__('resources/dms/strings.fields.confirmed_at'))
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->icon('heroicon-o-calendar'),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function revision(): TextEntry
    {
        return TextEntry::make('revision')
            ->label(__('resources/dms/strings.fields.revision'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/dms/strings.fields.status'))
            ->getStateUsing(fn($record) => DocumentStatus::tryFrom($record->status))
            ->badge();
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/dms/strings.fields.title'))
            ->size(TextSize::Medium)
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/dms/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function usersCount(): TextEntry
    {
        return TextEntry::make('users_count')
            ->label(__('resources/dms/strings.fields.users_count'))
            ->getStateUsing(fn($record) => count($record->users ?? []))
            ->badge()
            ->color('info')
            ->icon('heroicon-o-users');
    }

    public static function version(): TextEntry
    {
        return TextEntry::make('version')
            ->label(__('resources/dms/strings.fields.version'))
            ->badge()
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }
}
