<?php

namespace App\Filament\Resources\LinkResource\Schemas;

use App\Filament\Resources\LinkResource\Enums\LinkType;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;

class LinkTablePresenter
{
    public static function hasSmartRouting(): IconColumn
    {
        return IconColumn::make('has_smart_routing')
            ->label(__('resources/link/strings.fields.smart_routing'))
            ->getStateUsing(fn($record) => filled($record->internal_url))
            ->boolean()
            ->trueIcon('heroicon-o-cpu-chip')
            ->falseIcon('heroicon-o-minus')
            ->trueColor('warning')
            ->falseColor('gray')
            ->tooltip(fn($record) => filled($record->internal_url) ? __('resources/link/strings.fields.smart_routing_active', ['count' => count($record->extra ?? [])]) : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function icon(): ImageColumn
    {
        return ImageColumn::make('icon')
            ->label(__('resources/link/strings.fields.icon'))
            ->disk('public')
            ->square()
            ->imageSize(32)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function image(): ImageColumn
    {
        return ImageColumn::make('image')
            ->label(__('resources/link/strings.fields.image'))
            ->disk('public')
            ->square()
            ->imageSize(48)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function internalUrl(): TextColumn
    {
        return TextColumn::make('internal_url')
            ->label(__('resources/link/strings.fields.internal_url'))
            ->searchable()
            ->limit(40)
            ->placeholder('—')
            ->url(fn($state) => $state, true)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function linkType(): TextColumn
    {
        return TextColumn::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->getStateUsing(fn($record) => LinkType::tryFrom($record->link))
            ->badge()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function linkTypeFilter(): SelectFilter
    {
        return SelectFilter::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->options(LinkType::class)
            ->validationMessages([
                'in' => __('resources/link/strings.validation.link.in')
            ]);
    }

    public static function sequence(): TextColumn
    {
        return TextColumn::make('sequence')
            ->label(__('resources/link/strings.fields.sequence'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function smartRoutingFilter(): TernaryFilter
    {
        return TernaryFilter::make('smart_routing')
            ->label(__('resources/link/strings.fields.smart_routing'))
            ->trueLabel(__('resources/link/strings.filters.smart_routing_on'))
            ->falseLabel(__('resources/link/strings.filters.smart_routing_off'))
            ->queries(
                true: fn($q) => $q->whereNotNull('internal_url'),
                false: fn($q) => $q->whereNull('internal_url'),
            );
    }

    public static function typeGroup(): Group
    {
        return Group::make('link')
            ->label(__('resources/link/strings.fields.link_type'))
            ->getTitleFromRecordUsing(function ($record) {
                if ($record->link instanceof LinkType) return $record->link->getLabel();
                return is_string($record->link) ? LinkType::tryFrom($record->link)?->getLabel() ?? $record->link : $record->link;
            })
            ->collapsible();
    }

    public static function url(): TextColumn
    {
        return TextColumn::make('url')
            ->label(__('resources/link/strings.fields.url'))
            ->searchable()
            ->limit(50)
            ->tooltip(fn($state) => $state)
            ->url(fn($state) => $state, true)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function urlTitle(): TextColumn
    {
        return TextColumn::make('url_title')
            ->label(__('resources/link/strings.fields.url_title'))
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
