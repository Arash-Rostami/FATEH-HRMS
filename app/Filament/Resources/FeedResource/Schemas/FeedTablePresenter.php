<?php

namespace App\Filament\Resources\FeedResource\Schemas;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class FeedTablePresenter
{
    public static function category(): TextColumn
    {
        return TextColumn::make('category')
            ->label(__('resources/feed/strings.fields.category'))
            ->getStateUsing(fn($record) => FeedCategory::tryFrom($record->category))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function categoryFilter(): SelectFilter
    {
        return SelectFilter::make('category')
            ->label(__('resources/feed/strings.fields.category'))
            ->options(FeedCategory::class);
    }

    public static function categoryGroup(): Group
    {
        return Group::make('category')
            ->label(__('resources/feed/strings.fields.category'))
            ->getTitleFromRecordUsing(fn($r) => FeedCategory::tryFrom($r->category)?->getLabel() ?? $r->category)
            ->collapsible();
    }

    public static function commentsCount(): TextColumn
    {
        return TextColumn::make('comments_count')
            ->label(__('resources/feed/strings.fields.comments_count'))
            ->counts('comments')
            ->badge()
            ->color('primary')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function content(): TextColumn
    {
        return TextColumn::make('content')
            ->label(__('resources/feed/strings.fields.content'))
            ->limit(40)
            ->html()
            ->wrap()
            ->lineClamp(2)
            ->tooltip(fn($state) => strlen($state) > 40 ? strip_tags($state) : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/feed/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }


    public static function hasMediaFilter(): Filter
    {
        return Filter::make('has_media')
            ->label(__('resources/feed/strings.filters.has_media'))
            ->query(fn(Builder $query) => $query->whereRaw("(media_paths IS NOT NULL AND media_paths != '[]' AND JSON_LENGTH(media_paths) > 0)"))
            ->toggle();
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function mediaCount(): TextColumn
    {
        return TextColumn::make('media_count')
            ->label('#')
            ->getStateUsing(fn($record) => count($record->images) + count($record->videos))
            ->badge()
            ->color('info')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function mediaPreview(): ImageColumn
    {
        return ImageColumn::make('media_preview')
            ->label(__('resources/feed/strings.fields.media'))
            ->square()
            ->imageSize(40)
            ->disk('public')
            ->getStateUsing(fn($record): ?string => $record->images[0] ?? null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function reactionsCount(): TextColumn
    {
        return TextColumn::make('reactions_count')
            ->label(__('resources/feed/strings.fields.reactions_count'))
            ->counts('reactions')
            ->badge()
            ->color('warning')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function pollsCount(): TextColumn
    {
        return TextColumn::make('polls_count')
            ->label(__('resources/feed/strings.fields.polls_count'))
            ->counts('polls')
            ->badge()
            ->color('success')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/feed/strings.fields.creator'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/feed/strings.fields.creator'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload();
    }
}
