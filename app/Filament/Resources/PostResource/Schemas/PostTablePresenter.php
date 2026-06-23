<?php

namespace App\Filament\Resources\PostResource\Schemas;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PostTablePresenter
{
    public static function body(): TextColumn
    {
        return TextColumn::make('body')
            ->label(__('resources/post/strings.fields.body'))
            ->html()
            ->html()
            ->wrap()
            ->lineClamp(2)
            ->limit(80)
            ->tooltip(fn($record) => strip_tags($record->body ?? ''))
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/post/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function hasImageFilter(): Filter
    {
        return Filter::make('has_image')
            ->label(__('resources/post/strings.filters.has_image'))
            ->query(fn(Builder $query) => $query->whereRaw("(image IS NOT NULL AND TRIM(image) != '' AND image != 'NULL')"))
            ->toggle();
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function image(): ImageColumn
    {
        return ImageColumn::make('image')
            ->label(__('resources/post/strings.fields.image'))
            ->disk('public')
            ->square()
            ->imageSize(48)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function pinned(): ToggleColumn
    {
        return ToggleColumn::make('pinned')
            ->label(__('resources/post/strings.fields.pinned'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function pinnedFilter()
    {
        return Filter::make('pinned')
            ->label(__('resources/post/strings.fields.pinned'))
            ->query(fn(Builder $query) => $query->where('pinned', '=', 1))
            ->toggle();
    }

    public static function title(): TextColumn
    {
        return TextColumn::make('title')
            ->label(__('resources/post/strings.fields.title'))
            ->searchable()
            ->html()
            ->limit(60)
            ->tooltip(fn($state) => strlen($state) > 60 ? $state : null)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/post/strings.fields.user'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
