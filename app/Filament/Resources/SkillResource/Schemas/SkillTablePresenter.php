<?php

namespace App\Filament\Resources\SkillResource\Schemas;

use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;

class SkillTablePresenter
{
    public static function category(): TextColumn
    {
        return TextColumn::make('category')
            ->label(__('resources/skill/strings.fields.category'))
            ->badge()
            ->color('info')
            ->searchable()
            ->sortable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function categoryFilter(): SelectFilter
    {
        return SelectFilter::make('category')
            ->label(__('resources/skill/strings.fields.category'))
            ->options(fn(): array => \App\Models\Skill::whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->toArray())
            ->searchable();
    }

    public static function categoryGroup(): Group
    {
        return Group::make('category')
            ->label(__('resources/skill/strings.fields.category'))
            ->collapsible();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/skill/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function isActive(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label(__('resources/skill/strings.fields.is_active'))
            ->boolean()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isActiveFilter(): SelectFilter
    {
        return SelectFilter::make('is_active')
            ->label(__('resources/skill/strings.fields.is_active'))
            ->options([
                1 => __('resources/skill/strings.filters.active'),
                0 => __('resources/skill/strings.filters.inactive'),
            ]);
    }

    public static function membersCount(): TextColumn
    {
        return TextColumn::make('skill_users_count')
            ->label(__('resources/skill/strings.fields.members_count'))
            ->numeric()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/skill/strings.fields.name'))
            ->weight(FontWeight::Bold)
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
