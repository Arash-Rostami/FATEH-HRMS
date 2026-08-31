<?php

namespace App\Filament\Resources\ProjectResource\Schemas;

use App\Models\Department;
use App\Models\User;
use App\Services\ProjectTask\ReportingService;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\IconPosition;

class ProjectInfolistPresenter
{
    public static function name(): TextEntry
    {
        return TextEntry::make('name')
            ->label(__('resources/project/strings.fields.name'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-rectangle-stack');
    }

    public static function owner(): TextEntry
    {
        return TextEntry::make('owner.name')
            ->label(__('resources/project/strings.fields.owner'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function memberIds(): TextEntry
    {
        return TextEntry::make('member_ids')
            ->label(__('resources/project/strings.fields.member_ids'))
            ->formatStateUsing(fn($state) => User::whereIn('id', $state ?? [])->pluck('name')->implode('، ') ?: '—')
            ->columnSpanFull();
    }

    public static function departments(): TextEntry
    {
        return TextEntry::make('departments')
            ->label(__('resources/project/strings.fields.departments'))
            ->formatStateUsing(function ($state) {
                $codes = $state ?? [];
                if (empty($codes)) {
                    return '—';
                }

                return Department::getCachedModels()
                    ->only($codes)
                    ->map(fn($d) => $d->displayLabel())
                    ->implode('، ');
            })
            ->columnSpanFull();
    }

    public static function channel(): TextEntry
    {
        return TextEntry::make('channel.name')
            ->label(__('resources/project/strings.fields.channel'))
            ->placeholder('—')
            ->icon('heroicon-o-chat-bubble-left-right');
    }

    public static function tasksCount(): TextEntry
    {
        return TextEntry::make('tasks_count')
            ->label(__('resources/project/strings.fields.tasks_count'))
            ->badge()
            ->color('info');
    }

    public static function progress(): TextEntry
    {
        return TextEntry::make('progress')
            ->label(__('resources/project/strings.fields.progress'))
            ->getStateUsing(fn($record) => app(ReportingService::class)->summary($record->id, 0)['percent'] . '٪')
            ->badge();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/project/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/project/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function deletedAt(): TextEntry
    {
        return TextEntry::make('deleted_at')
            ->label(__('resources/project/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : null)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->placeholder('—')
            ->color('danger')
            ->icon('heroicon-o-trash');
    }

    public static function settingsSummary(): TextEntry
    {
        return TextEntry::make('settings_summary')
            ->label(__('resources/project/strings.fields.settings'))
            ->getStateUsing(fn($record) => $record->settingsSummary())
            ->icon('heroicon-o-cog-6-tooth')
            ->columnSpanFull();
    }

    public static function prunableWarning(): TextEntry
    {
        return TextEntry::make('prune_info')
            ->label(__('resources/project/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->hidden(fn($record) => !$record->deleted_at);
    }
}
