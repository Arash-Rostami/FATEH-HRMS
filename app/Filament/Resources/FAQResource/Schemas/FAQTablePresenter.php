<?php

namespace App\Filament\Resources\FAQResource\Schemas;

use App\Models\Department;
use App\Models\FAQ;
use App\Models\User;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Model;

class FAQTablePresenter
{
    public static function answer(): TextColumn
    {
        return TextColumn::make('answer')
            ->label(__('resources/faq/strings.fields.answer'))
            ->html()
            ->wrap()
            ->lineClamp(2)
            ->weight(FontWeight::Light)
            ->limit(60)
            ->tooltip(fn($state) => strlen($state) > 60 ? strip_tags($state) : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function category(): TextColumn
    {
        return TextColumn::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->badge()
            ->color('info')
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function categoryFilter(): SelectFilter
    {
        return SelectFilter::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->options(fn(): array => FAQ::cachedCategoryFilter())
            ->searchable();
    }

    public static function categoryGroup(): Group
    {
        return Group::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->collapsible();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/faq/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function department(): TextColumn
    {
        return TextColumn::make('department.name')
            ->label(__('resources/faq/strings.fields.department'))
            ->formatStateUsing(fn(?Model $record): string => $record?->department?->displayLabel() ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department?->tooltipLabel() ?? '-')
            ->sortable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/faq/strings.fields.department'))
            ->searchable()
            ->options(fn() => Department::getCachedOptions()->toArray());
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/faq/strings.fields.department'))
            ->getTitleFromRecordUsing(fn($record): string => $record->department?->displayLabel() ?? __('resources/faq/strings.no_department'))
            ->collapsible();
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function question(): TextColumn
    {
        return TextColumn::make('question')
            ->label(__('resources/faq/strings.fields.question'))
            ->html()
            ->weight(FontWeight::Light)
            ->limit(60)
            ->tooltip(fn($state) => strlen($state) > 60 ? strip_tags($state) : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/faq/strings.fields.user'))
            ->sortable()
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/faq/strings.fields.user'))
            ->options(fn() => User::getCachedAllOptions()->toArray())
            ->searchable();
    }

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/faq/strings.fields.user'))
            ->getTitleFromRecordUsing(fn($record): string => $record->user?->name ?? '-')
            ->collapsible();
    }
}
