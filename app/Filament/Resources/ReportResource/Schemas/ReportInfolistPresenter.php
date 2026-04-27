<?php

namespace App\Filament\Resources\ReportResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;

class ReportInfolistPresenter
{
    public static function active(): IconEntry
    {
        return IconEntry::make('active')
            ->label(__('resources/report/strings.fields.active'))
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/report/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function department(): TextEntry
    {
        return TextEntry::make('department.name')
            ->label(__('resources/report/strings.fields.department'))
            ->formatStateUsing(fn(?Model $record): string => $record?->department->description ?? $record?->department->name ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department->name ?? $record?->department->description ?? '-')
            ->badge()
            ->color('info')
            ->placeholder('-');
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/report/strings.fields.description'))
            ->html()
            ->placeholder('-')
            ->columnSpanFull();
    }


    public static function fileType(): TextEntry
    {
        return TextEntry::make('file_type')
            ->label(__('resources/report/strings.fields.file_type'))
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'pdf' => 'danger',
                'docx', 'doc' => 'info',
                default => 'gray'
            });
    }

    public static function thumbnail(): ImageEntry
    {
        return ImageEntry::make('thumbnail')
            ->label(__('resources/report/strings.fields.thumbnail'))
            ->imageHeight(100)
            ->circular(false)
            ->columnSpanFull();
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/report/strings.fields.title'))
            ->html()
            ->weight(FontWeight::Bold)
            ->size(TextSize::Large);
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/report/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/report/strings.fields.user'))
            ->badge()
            ->color('gray')
            ->placeholder('-');
    }
}
