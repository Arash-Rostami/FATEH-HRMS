<?php

namespace App\Filament\Resources\OnboardingResource\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class OnboardingTablePresenter
{
    public static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label(__('resources/onboarding/strings.fields.is_active'))
            ->trueLabel(__('resources/onboarding/strings.filters.active'))
            ->falseLabel(__('resources/onboarding/strings.filters.inactive'));
    }

    public static function audienceFilter(): TernaryFilter
    {
        return TernaryFilter::make('audience')
            ->label(__('resources/onboarding/strings.filters.audience'))
            ->trueLabel(__('resources/onboarding/strings.filters.personal'))
            ->falseLabel(__('resources/onboarding/strings.filters.global'))
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('user_id'),
                false: fn(Builder $q) => $q->whereNull('user_id'),
            );
    }

    public static function audienceGroup(): Group
    {
        return Group::make('user_id')
            ->label(__('resources/onboarding/strings.fields.user_id'))
            ->getTitleFromRecordUsing(fn($r) => $r->user?->name ?? __('resources/onboarding/strings.fields.global_audience'))
            ->collapsible();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/onboarding/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function extrasCount(): TextColumn
    {
        return TextColumn::make('extras_count')
            ->label(__('resources/onboarding/strings.fields.extras'))
            ->getStateUsing(fn($record) => count((array)($record->extras ?? [])))
            ->badge()
            ->color('warning')
            ->icon('heroicon-o-squares-2x2')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function guidesCount(): TextColumn
    {
        return TextColumn::make('guides_count')
            ->label(__('resources/onboarding/strings.fields.guides'))
            ->getStateUsing(fn($record) => count($record->guides ?? []))
            ->badge()
            ->color('info')
            ->icon('heroicon-o-document')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isActive(): ToggleColumn
    {
        return ToggleColumn::make('is_active')
            ->label(__('resources/onboarding/strings.fields.is_active'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function sections(): TextColumn
    {
        return TextColumn::make('sections_summary')
            ->label(__('resources/onboarding/strings.fields.sections'))
            ->getStateUsing(function ($record): string {
                $map = [
                    'welcome' => __('resources/onboarding/strings.fields.welcome'),
                    'mission' => __('resources/onboarding/strings.fields.mission'),
                    'vision' => __('resources/onboarding/strings.fields.vision'),
                    'schedule' => __('resources/onboarding/strings.fields.schedule'),
                ];
                return collect($map)
                    ->filter(fn($_, $key) => filled($record->$key))
                    ->values()
                    ->implode('، ');
            })
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/onboarding/strings.fields.user_id'))
            ->placeholder(__('resources/onboarding/strings.fields.global_audience'))
            ->icon('heroicon-o-user')
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function videosCount(): TextColumn
    {
        return TextColumn::make('videos_count')
            ->label(__('resources/onboarding/strings.fields.videos'))
            ->getStateUsing(fn($record) => count($record->videos ?? []))
            ->badge()
            ->color('primary')
            ->icon('heroicon-o-video-camera')
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
