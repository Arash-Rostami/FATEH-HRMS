<?php

namespace App\Filament\Resources\AdResource\Schemas;

use App\Filament\Resources\AdResource\Enums\AdGender;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class AdTablePresenter
{
    public static function active(): ToggleColumn
    {
        return ToggleColumn::make('active')
            ->label(__('resources/ad/strings.fields.active'))
            ->onIcon('heroicon-m-check-circle')
            ->offIcon('heroicon-m-x-circle')
            ->onColor('success')
            ->offColor('danger')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('active')
            ->label(__('resources/ad/strings.fields.active'))
            ->trueLabel(__('resources/ad/strings.status.active'))
            ->falseLabel(__('resources/ad/strings.status.inactive'));
    }

    public static function activeGroup(): Group
    {
        return Group::make('active')
            ->label(__('resources/ad/strings.fields.active'))
            ->getTitleFromRecordUsing(fn($record): string => $record->active
                ? __('resources/ad/strings.status.active')
                : __('resources/ad/strings.status.inactive'))
            ->collapsible();
    }

    public static function certificate(): TextColumn
    {
        return TextColumn::make('certificate')
            ->label(__('resources/ad/strings.fields.certificate'))
            ->limit(60)
            ->tooltip(fn($record) => $record->certificate)
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/ad/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }


    public static function experience(): TextColumn
    {
        return TextColumn::make('experience')
            ->label(__('resources/ad/strings.fields.experience'))
            ->limit(60)
            ->tooltip(fn($record) => $record->experience)
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function gender(): TextColumn
    {
        return TextColumn::make('gender')
            ->label(__('resources/ad/strings.fields.gender'))
            ->badge()
            ->color(fn(string $state): string => AdGender::from($state)->getColor())
            ->formatStateUsing(fn(string $state): string => AdGender::from($state)->getLabel())
            ->icon(fn(string $state): string => AdGender::from($state)->getIcon())
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function genderFilter(): SelectFilter
    {
        return SelectFilter::make('gender')
            ->label(__('resources/ad/strings.fields.gender'))
            ->options(AdGender::class)
            ->validationMessages([
                'in' => __('resources/ad/strings.validation.gender.in')
            ]);
    }

    public static function genderGroup(): Group
    {
        return Group::make('gender')
            ->label(__('resources/ad/strings.fields.gender'))
            ->getTitleFromRecordUsing(fn($record): string => AdGender::from($record->gender)->getLabel())
            ->collapsible();
    }

    public static function hasCertificateFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_certificate')
            ->label(__('resources/ad/strings.filters.has_certificate'))
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('certificate'),
                false: fn(Builder $query) => $query->whereNull('certificate'),
            );
    }

    public static function hasExperienceFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_experience')
            ->label(__('resources/ad/strings.filters.has_experience'))
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('experience'),
                false: fn(Builder $query) => $query->whereNull('experience'),
            );
    }

    public static function hasSkillFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_skill')
            ->label(__('resources/ad/strings.filters.has_skill'))
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('skill'),
                false: fn(Builder $query) => $query->whereNull('skill'),
            );
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function link(): TextColumn
    {
        return TextColumn::make('link')
            ->label(__('resources/ad/strings.fields.link'))
            ->url(fn($record) => $record->link)
            ->openUrlInNewTab()
            ->limit(35)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function position(): TextColumn
    {
        return TextColumn::make('position')
            ->label(__('resources/ad/strings.fields.position'))
            ->searchable()
            ->sortable()
            ->placeholder(__('resources/ad/strings.fields.position_empty'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function skill(): TextColumn
    {
        return TextColumn::make('skill')
            ->label(__('resources/ad/strings.fields.skill'))
            ->limit(60)
            ->tooltip(fn($record) => $record->skill)
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
