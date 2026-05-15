<?php

namespace App\Filament\Resources\SuggestionResource\Schemas;

use App\Filament\Resources\SuggestionResource\Enums\SuggestionStage;
use App\Models\Department;
use App\Models\Suggestion;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class SuggestionTablePresenter
{
    public static function agreeCount(): TextColumn
    {
        return TextColumn::make('agree_count')
            ->label(__('resources/suggestion/strings.fields.agree_count'))
            ->badge()
            ->color('success')
            ->icon('heroicon-o-hand-thumb-up')
            ->alignCenter()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function attachment(): IconColumn
    {
        return IconColumn::make('attachment')
            ->label(__('resources/suggestion/strings.fields.attachment'))
            ->boolean()
            ->getStateUsing(fn($record): bool => filled($record->attachment))
            ->trueIcon('heroicon-o-paper-clip')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('info')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/suggestion/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('departments')
            ->label(__('resources/suggestion/strings.filters.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['value'] ?? null, fn($q, $code) => $q->whereJsonContains('departments', $code))
            );
    }

    public static function disagreeCount(): TextColumn
    {
        return TextColumn::make('disagree_count')
            ->label(__('resources/suggestion/strings.fields.disagree_count'))
            ->badge()
            ->color('danger')
            ->icon('heroicon-o-hand-thumb-down')
            ->alignCenter()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function hasFileFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_attachment')
            ->label(__('resources/suggestion/strings.filters.has_file'))
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('attachment'),
                false: fn(Builder $q) => $q->whereNull('attachment'),
            );
    }

    public static function hasReferralFilter(): Filter
    {
        return Filter::make('has_referral')
            ->label(__('resources/suggestion/strings.filters.has_referral'))
            ->query(fn(Builder $query) => $query->whereHas('reviews', fn(Builder $q) => $q
                ->where('department_id', 'MA')
                ->whereNotNull('referral')
            ))
            ->toggle();
    }

    public static function neutralCount(): TextColumn
    {
        return TextColumn::make('neutral_count')
            ->label(__('resources/suggestion/strings.fields.neutral_count'))
            ->badge()
            ->color('gray')
            ->icon('heroicon-o-minus-circle')
            ->alignCenter()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function selfFillFilter(): TernaryFilter
    {
        return TernaryFilter::make('self_fill')
            ->label(__('resources/suggestion/strings.filters.self_fill'));
    }

    public static function serial(): TextColumn
    {
        return TextColumn::make('serial')
            ->label(__('resources/suggestion/strings.fields.serial'))
            ->getStateUsing(fn($record): string => sprintf(
                'SN-%s-%06d',
                $record->created_at?->format('Ymd') ?? '00000000',
                $record->id,
            ))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->badge()
            ->color('gray')
            ->copyable()
            ->searchable(query: function (Builder $query, string $search): Builder {
                $term = "%{$search}%";
                return $query->where(fn(Builder $q) => $q
                    ->where('title', 'like', $term)
                    ->orWhereRaw(
                        "CONCAT('SN-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 6, '0')) like ?",
                        [$term]
                    )
                );
            })
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function stage(): TextColumn
    {
        return TextColumn::make('stage')
            ->label(__('resources/suggestion/strings.fields.stage'))
            ->getStateUsing(fn($record) => SuggestionStage::tryFrom($record->stage))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function stageFilter(): SelectFilter
    {
        return SelectFilter::make('stage')
            ->label(__('resources/suggestion/strings.filters.stage'))
            ->options(Suggestion::STAGES);
    }

    public static function stageGroup(): Group
    {
        return Group::make('stage')
            ->label(__('resources/suggestion/strings.fields.stage'))
            ->getTitleFromRecordUsing(
                fn($record): string => SuggestionStage::tryFrom($record?->stage)?->getLabel()
                    ?? (string)$record?->stage
            )
            ->getKeyFromRecordUsing(fn($record): string => (string)$record?->stage)
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function submitter(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/suggestion/strings.fields.submitter'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function submitterFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/suggestion/strings.filters.submitter'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }

    public static function submitterGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/suggestion/strings.fields.submitter'))
            ->getTitleFromRecordUsing(fn($record): string => $record?->user?->name ?: '—')
            ->getKeyFromRecordUsing(fn($record): string => (string)($record?->user_id ?? 'unknown'))
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/suggestion/strings.fields.title'))
            ->searchable()
            ->sortable()
            ->limit(60)
            ->wrap()
            ->tooltip(fn($state) => strlen((string)$state) > 60 ? $state : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
