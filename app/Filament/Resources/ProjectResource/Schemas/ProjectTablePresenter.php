<?php

namespace App\Filament\Resources\ProjectResource\Schemas;

use App\Services\ProjectTask\ReportingService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ProjectTablePresenter
{
    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/project/strings.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/project/strings.fields.name'))
            ->searchable()
            ->sortable()
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function owner(): TextColumn
    {
        return TextColumn::make('owner.name')
            ->label(__('resources/project/strings.fields.owner'))
            ->searchable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function audienceSummary(): TextColumn
    {
        return TextColumn::make('audience_summary')
            ->label(__('resources/project/strings.fields.member_ids'))
            ->getStateUsing(fn($record) => count($record->member_ids ?? []) . ' عضو، ' . count($record->departments ?? []) . ' دپارتمان')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function channel(): TextColumn
    {
        return TextColumn::make('channel.name')
            ->label(__('resources/project/strings.fields.channel'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function tasksCount(): TextColumn
    {
        return TextColumn::make('tasks_count')
            ->label(__('resources/project/strings.fields.tasks_count'))
            ->badge()
            ->color('info')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function progress(): TextColumn
    {
        return TextColumn::make('progress')
            ->label(__('resources/project/strings.fields.progress'))
            ->getStateUsing(fn($record) => app(ReportingService::class)->summary($record->id, 0)['percent'] . '٪')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/project/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function deletedAt(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->label(__('resources/project/strings.fields.deleted_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function settingsSummary(): TextColumn
    {
        return TextColumn::make('settings_summary')
            ->label(__('resources/project/strings.fields.settings'))
            ->getStateUsing(fn($record) => $record->settingsSummary())
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function prunableWarning(): TextColumn
    {
        return TextColumn::make('prune_status')
            ->label(__('resources/project/strings.fields.prune_status'))
            ->getStateUsing(fn($record) => $record->pruneStatusText())
            ->color(fn($record) => $record->pruneStatusColor())
            ->badge()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function pruningSoonFilter(): Filter
    {
        return Filter::make('pruning_soon')
            ->label(__('resources/project/strings.filters.pruning_soon'))
            ->query(fn(Builder $query) => $query
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<=', now()->subDays(30))
            )
            ->toggle();
    }
}
