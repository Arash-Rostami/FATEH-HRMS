<?php

namespace App\Filament\Resources\AiReportResource\Schemas;

use App\Models\AiReport;
use App\Models\User;
use App\Traits\FilamentFilters;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiReportTablePresenter
{
    use FilamentFilters;

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/ai_report/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function draftFilter(): TernaryFilter
    {
        return TernaryFilter::make('saved_at')
            ->label(__('resources/ai_report/strings.filters.saved'))
            ->trueLabel(__('resources/ai_report/strings.filters.saved_true'))
            ->falseLabel(__('resources/ai_report/strings.filters.saved_false'))
            ->native(false)
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('saved_at'),
                false: fn(Builder $query) => $query->whereNull('saved_at'),
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/ai_report/strings.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function model(): TextColumn
    {
        return TextColumn::make('model')
            ->label(__('resources/ai_report/strings.fields.model'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function owner(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/ai_report/strings.fields.owner'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function ownerFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/ai_report/strings.fields.owner'))
            ->options(fn() => User::getCachedAllOptions()->toArray())
            ->searchable();
    }

    public static function ownerGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/ai_report/strings.fields.owner'))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->user?->name ?? '—')
            ->collapsible();
    }

    public static function recipients(): TextColumn
    {
        return TextColumn::make('recipients')
            ->label(__('resources/ai_report/strings.fields.recipients'))
            ->getStateUsing(fn(AiReport $record) => convertToPersian(count($record->recipients ?? [])))
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function savedAt(): TextColumn
    {
        return TextColumn::make('saved_at')
            ->label(__('resources/ai_report/strings.fields.saved_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function staleDraftFilter(): Filter
    {
        return Filter::make('stale_drafts')
            ->label(__('resources/ai_report/strings.filters.stale_drafts', ['days' => convertToPersian(config('ai_report.stale_draft_days'))]))
            ->toggle()
            ->query(fn(Builder $query) => $query->whereNull('saved_at')->where('created_at', '<', now()->subDays(config('ai_report.stale_draft_days'))));
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/ai_report/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => __("resources/ai_report/strings.statuses.{$state}"))
            ->color(fn(string $state): string => AiReport::STATUS_COLORS[$state] ?? 'info')
            ->icon(fn(string $state): string => AiReport::STATUS_ICONS[$state] ?? 'heroicon-o-arrow-path')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/ai_report/strings.fields.status'))
            ->options(fn() => array_combine(
                [AiReport::STATUS_GENERATING, AiReport::STATUS_READY, AiReport::STATUS_FAILED],
                [
                    __('resources/ai_report/strings.statuses.generating'),
                    __('resources/ai_report/strings.statuses.ready'),
                    __('resources/ai_report/strings.statuses.failed'),
                ],
            ));
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/ai_report/strings.fields.status'))
            ->getTitleFromRecordUsing(fn(Model $record): string => __("resources/ai_report/strings.statuses.{$record->status}"))
            ->collapsible();
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/ai_report/strings.fields.title'))
            ->placeholder('—')
            ->searchable()
            ->limit(40)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function tokensTotal(): TextColumn
    {
        return TextColumn::make('tokens_total')
            ->label(__('resources/ai_report/strings.fields.tokens_total'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state) : '—')
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
