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
use Illuminate\Support\Facades\Cache;

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
            ->options(fn(): array => Cache::remember(
                'faq_categories_filter_options', now()->addDay(),
                fn() => FAQ::distinct()->orderBy('category')->pluck('category', 'category')->toArray()
            ))
            ->searchable()
            ->validationMessages([
                'in' => __('resources/faq/strings.validation.category.in')
            ]);
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
            ->formatStateUsing(fn(?Model $record): string => $record?->department->description ?? $record?->department->name ?? '-')
            ->tooltip(fn(?Model $record): string => $record?->department->name ?? $record?->department->description ?? '-')
            ->sortable()
            ->placeholder('-')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department_id')
            ->label(__('resources/faq/strings.fields.department'))
            ->searchable()
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->validationMessages([
                'in' => __('resources/faq/strings.validation.department_id.in')
            ]);
    }

    public static function departmentGroup(): Group
    {
        return Group::make('department.description')
            ->label(__('resources/faq/strings.fields.department'))
            ->getTitleFromRecordUsing(fn($record): string => $record->department?->description ?? __('resources/faq/strings.no_department'))
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
            ->options(fn() => Cache::remember(
                'user_filter_options', now()->addDay(),
                fn() => User::orderBy('name')->pluck('name', 'id')->toArray()
            ))
            ->searchable()
            ->validationMessages([
                'in' => __('resources/faq/strings.validation.user_id.in')
            ]);
    }

    public static function userGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/faq/strings.fields.user'))
            ->getTitleFromRecordUsing(fn($record): string => $record->user?->name ?? '-')
            ->collapsible();
    }
}
