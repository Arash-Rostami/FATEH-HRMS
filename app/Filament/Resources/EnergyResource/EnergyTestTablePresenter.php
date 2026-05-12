<?php

namespace App\Filament\Resources\EnergyTestResource\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class EnergyTestTablePresenter
{
    // ─── Score color helper ───────────────────────────────────────────────────

    private static function scoreColor(?float $score): string
    {
        return match (true) {
            $score === null      => 'gray',
            $score >= 70         => 'success',
            $score >= 45         => 'warning',
            default              => 'danger',
        };
    }

    // ─── Columns ──────────────────────────────────────────────────────────────

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/energy_test/strings.fields.user'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function overallScore(): TextColumn
    {
        return TextColumn::make('overall_score')
            ->label(__('resources/energy_test/strings.fields.overall_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function mindScore(): TextColumn
    {
        return TextColumn::make('mind_score')
            ->label(__('resources/energy_test/strings.fields.mind_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function emotionScore(): TextColumn
    {
        return TextColumn::make('emotion_score')
            ->label(__('resources/energy_test/strings.fields.emotion_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function physiqueScore(): TextColumn
    {
        return TextColumn::make('physique_score')
            ->label(__('resources/energy_test/strings.fields.physique_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function soulScore(): TextColumn
    {
        return TextColumn::make('soul_score')
            ->label(__('resources/energy_test/strings.fields.soul_score'))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function monthIndex(): TextColumn
    {
        return TextColumn::make('month_index')
            ->label(__('resources/energy_test/strings.fields.month_index'))
            ->badge()
            ->color('gray')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function completedAt(): TextColumn
    {
        return TextColumn::make('completed_at')
            ->label(__('resources/energy_test/strings.fields.completed_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/energy_test/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    // ─── Groups ───────────────────────────────────────────────────────────────

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/energy_test/strings.fields.user'))
            ->collapsible();
    }

    public static function monthGroup(): Group
    {
        return Group::make('month_index')
            ->label(__('resources/energy_test/strings.fields.month_index'))
            ->collapsible();
    }

    // ─── Filters ──────────────────────────────────────────────────────────────

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/energy_test/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }

    public static function lowScoreFilter(): Filter
    {
        return Filter::make('low_score')
            ->label(__('resources/energy_test/strings.filters.low_score'))
            ->query(fn(Builder $q) => $q->where('overall_score', '<', 45))
            ->toggle();
    }

    public static function scoreRangeFilter(): Filter
    {
        return Filter::make('score_range')
            ->label(__('resources/energy_test/strings.filters.score_range'))
            ->form([
                TextInput::make('min')
                    ->label(__('resources/energy_test/strings.filters.score_min'))
                    ->numeric()->minValue(0)->maxValue(100),
                TextInput::make('max')
                    ->label(__('resources/energy_test/strings.filters.score_max'))
                    ->numeric()->minValue(0)->maxValue(100),
            ])
            ->query(fn(Builder $q, array $data) => $q
                ->when(filled($data['min']), fn($q) => $q->where('overall_score', '>=', $data['min']))
                ->when(filled($data['max']), fn($q) => $q->where('overall_score', '<=', $data['max']))
            );
    }

    public static function dateRangeFilter(): Filter
    {
        return Filter::make('completed_at_range')
            ->label(__('resources/energy_test/strings.filters.date_range'))
            ->form([
                DatePicker::make('from')->label(__('resources/energy_test/strings.filters.date_from')),
                DatePicker::make('until')->label(__('resources/energy_test/strings.filters.date_until')),
            ])
            ->query(fn(Builder $q, array $data) => $q
                ->when($data['from'], fn($q, $v) => $q->whereDate('completed_at', '>=', $v))
                ->when($data['until'], fn($q, $v) => $q->whereDate('completed_at', '<=', $v))
            );
    }

    public static function lastMonthFilter(): Filter
    {
        return Filter::make('last_month')
            ->label(__('resources/energy_test/strings.filters.last_month'))
            ->query(fn(Builder $q) => $q->where('completed_at', '>=', now()->subDays(30)))
            ->toggle();
    }
}
