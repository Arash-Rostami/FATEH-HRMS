<?php

namespace App\Filament\Resources\LetterheadResource\Schemas;

use App\Models\Letterhead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Model;

class LetterheadTablePresenter
{
    public static function childrenCount(): TextColumn
    {
        return TextColumn::make('children_count')
            ->label(__('resources/correspondence/strings.admin.letterhead.children'))
            ->formatStateUsing(fn ($state) => $state !== null ? convertToPersian((int) $state) : '—')
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function correspondencesCount(): TextColumn
    {
        return TextColumn::make('correspondences_count')
            ->label(__('resources/correspondence/strings.admin.letterhead.letters'))
            ->formatStateUsing(fn ($state) => $state !== null ? convertToPersian((int) $state) : '—')
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/correspondence/strings.admin.fields.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/correspondence/strings.admin.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function kind(): TextColumn
    {
        return TextColumn::make('kind')
            ->label(__('resources/correspondence/strings.admin.letterhead.kind'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.letterhead.kind.{$state}"))
            ->icon(fn (string $state): string => $state === Letterhead::KIND_REGISTRY
                ? 'heroicon-o-archive-box'
                : 'heroicon-o-document-text')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function kindFilter(): SelectFilter
    {
        return SelectFilter::make('kind')
            ->label(__('resources/correspondence/strings.admin.letterhead.kind'))
            ->options(Letterhead::kindLabels());
    }

    public static function kindGroup(): Group
    {
        return Group::make('kind')
            ->label(__('resources/correspondence/strings.admin.letterhead.kind'))
            ->getTitleFromRecordUsing(fn (Model $record): string => __("resources/correspondence/strings.letterhead.kind.{$record->kind}"))
            ->collapsible();
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/correspondence/strings.admin.letterhead.name'))
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function parent(): TextColumn
    {
        return TextColumn::make('parent.name')
            ->label(__('resources/correspondence/strings.admin.letterhead.parent'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function pattern(): TextColumn
    {
        return TextColumn::make('pattern')
            ->label(__('resources/correspondence/strings.admin.letterhead.pattern'))
            ->placeholder('—')
            ->limit(30)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function size(): TextColumn
    {
        return TextColumn::make('size')
            ->label(__('resources/correspondence/strings.admin.letterhead.size'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.letterhead.size.{$state}"))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function startAt(): TextColumn
    {
        return TextColumn::make('start_at')
            ->label(__('resources/correspondence/strings.admin.letterhead.start_at'))
            ->formatStateUsing(fn ($state) => convertToPersian((int) $state))
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
