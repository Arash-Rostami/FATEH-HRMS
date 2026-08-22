<?php

namespace App\Filament\Resources\EnergyResource\Schemas;

use App\Traits\FilamentFilters;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnergyTestTablePresenter
{
    use FilamentFilters;

    public static function completedAt(): TextColumn
    {
        return TextColumn::make('completed_at')
            ->label(__('resources/energy/strings.fields.completed_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/energy/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function dateRangeFilter(): Filter
    {
        return self::jalaliDateRangeFilter(
            'completed_at_range',
            'completed_at',
            __('resources/energy/strings.filters.date_range'),
            __('resources/energy/strings.filters.date_from'),
            __('resources/energy/strings.filters.date_until'),
        );
    }

    public static function emotionScore(): TextColumn
    {
        return TextColumn::make('emotion_score')
            ->label(__('resources/energy/strings.fields.emotion_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state, 3, 2))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/energy/strings.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function lastMonthFilter(): Filter
    {
        return Filter::make('last_month')
            ->label(__('resources/energy/strings.filters.last_month'))
            ->query(fn(Builder $query) => $query->where('completed_at', '>=', now()->subDays(30)))
            ->toggle();
    }

    public static function lowScoreFilter(): Filter
    {
        return Filter::make('low_score')
            ->label(__('resources/energy/strings.filters.low_score'))
            ->toggle()
            ->query(fn(Builder $query) => $query->where('overall_score', '<=', 8));
    }

    public static function mindScore(): TextColumn
    {
        return TextColumn::make('mind_score')
            ->label(__('resources/energy/strings.fields.mind_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state, 3, 2))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function monthGroup(): Group
    {
        return Group::make('month_index')
            ->label(__('resources/energy/strings.fields.completed_at'))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->completed_at ? toJalali($record->completed_at, 'F Y') : '—')
            ->collapsible();
    }

    public static function overallScore(): TextColumn
    {
        return TextColumn::make('overall_score')
            ->label(__('resources/energy/strings.fields.overall_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function physiqueScore(): TextColumn
    {
        return TextColumn::make('physique_score')
            ->label(__('resources/energy/strings.fields.physique_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state, 3, 2))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function scoreRangeFilter(): Filter
    {
        return Filter::make('score_range')
            ->label(__('resources/energy/strings.filters.score_range'))
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('min')
                        ->label(__('resources/energy/strings.filters.score_min'))
                        ->numeric()->minValue(0)->maxValue(20),
                    TextInput::make('max')
                        ->label(__('resources/energy/strings.filters.score_max'))
                        ->numeric()->minValue(0)->maxValue(20)
                ])
            ])
            ->query(fn(Builder $query, array $data) => $query
                ->when(filled($data['min']), fn($query) => $query->where('overall_score', '>=', $data['min']))
                ->when(filled($data['max']), fn($query) => $query->where('overall_score', '<=', $data['max']))
            );
    }

    public static function soulScore(): TextColumn
    {
        return TextColumn::make('soul_score')
            ->label(__('resources/energy/strings.fields.soul_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state, 3, 2))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/energy/strings.fields.user'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/energy/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/energy/strings.fields.user'))
            ->getTitleFromRecordUsing(fn(Model $record): string => $record->user?->name ?? '—')
            ->collapsible();
    }

    private static function scoreColor(?float $score, float $danger = 12, float $warning = 9): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= $danger => 'danger',
            $score >= $warning => 'warning',
            default => 'success',
        };
    }
}
