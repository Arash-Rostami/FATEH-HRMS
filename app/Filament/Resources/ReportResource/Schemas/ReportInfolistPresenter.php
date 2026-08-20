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

    public static function audience(): TextEntry
    {
        return TextEntry::make('audience')
            ->label(__('resources/report/strings.fields.audience'))
            ->placeholder(__('resources/report/strings.filters.visibility_public'))
            ->badge()
            ->color(fn($record) => $record->is_public ? 'success' : ($record->audience_departments->count() > 1 ? 'info' : 'warning'))
            ->icon(fn($record) => $record->is_public
                ? 'heroicon-o-globe-alt'
                : ($record->audience_departments->count() > 1 ? 'heroicon-o-users' : 'heroicon-o-lock-closed'))
            ->getStateUsing(function ($record) {
                $models = $record->audience_departments;
                if ($models->isEmpty()) {
                    return null;
                }
                return $models->map(fn($d) => $d->displayLabel())->join('، ');
            });
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
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
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

    public static function expiresAt(): TextEntry
    {
        return TextEntry::make('expires_at')
            ->label(__('resources/report/strings.fields.expires_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color(fn($state) => $state && $state < now() ? 'danger' : 'gray')
            ->placeholder('-');
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

    public static function pinned(): IconEntry
    {
        return IconEntry::make('pinned')
            ->label(__('resources/report/strings.fields.pinned'))
            ->boolean()
            ->trueIcon('heroicon-o-bookmark')
            ->falseIcon('heroicon-o-bookmark')
            ->trueColor('warning')
            ->falseColor('gray');
    }

    public static function reportDate(): TextEntry
    {
        return TextEntry::make('report_date')
            ->label(__('resources/report/strings.fields.report_date'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->color('gray')
            ->placeholder('-');
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