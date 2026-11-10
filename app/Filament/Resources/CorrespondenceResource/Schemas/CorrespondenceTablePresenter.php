<?php

namespace App\Filament\Resources\CorrespondenceResource\Schemas;

use App\Models\Correspondence;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CorrespondenceTablePresenter
{
    private static ?Collection $names = null;

    public static function names(): Collection
    {
        return self::$names ??= User::getCachedAllOptions();
    }

    public static function approved(): TextColumn
    {
        return TextColumn::make('approved_at')
            ->label(__('resources/correspondence/strings.admin.fields.approved'))
            ->placeholder('—')
            ->formatStateUsing(fn ($state) => toJalali($state, 'Y/m/d H:i'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function classificationColor(string $state): string
    {
        return match ($state) {
            Correspondence::CLASSIFICATION_CONFIDENTIAL => 'warning',
            Correspondence::CLASSIFICATION_SECRET => 'danger',
            default => 'success',
        };
    }

    public static function urgencyColor(string $state): string
    {
        return match ($state) {
            Correspondence::URGENCY_URGENT => 'warning',
            Correspondence::URGENCY_IMMEDIATE => 'danger',
            default => 'gray',
        };
    }

    public static function author(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/correspondence/strings.fields.owner'))
            ->searchable()
            ->sortable()
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function authorGroup(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/correspondence/strings.fields.owner'))
            ->getTitleFromRecordUsing(fn (Model $record): string => $record->user?->name ?? '—')
            ->collapsible();
    }

    public static function awaited(): TextColumn
    {
        return TextColumn::make('awaiting_user_id')
            ->label(__('resources/correspondence/strings.admin.fields.awaited'))
            ->placeholder('—')
            ->getStateUsing(fn (Correspondence $record): ?string => $record->awaiting_user_id
                ? (string) self::names()->get($record->awaiting_user_id, __('resources/correspondence/strings.admin.fields.deleted_user'))
                : null)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function classification(): TextColumn
    {
        return TextColumn::make('classification')
            ->label(__('resources/correspondence/strings.fields.classification'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.classification.{$state}"))
            ->color(fn (string $state): string => self::classificationColor($state))
            ->icon(fn (string $state): string => $state !== Correspondence::CLASSIFICATION_NORMAL
                ? 'heroicon-o-lock-closed'
                : 'heroicon-o-lock-open')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function direction(): TextColumn
    {
        return TextColumn::make('direction')
            ->label(__('resources/correspondence/strings.filters.direction'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.direction.{$state}"))
            ->icon(fn (string $state): string => $state === Correspondence::DIRECTION_IN
                ? 'heroicon-o-inbox-arrow-down'
                : 'heroicon-o-paper-airplane')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function directionFilter(): TernaryFilter
    {
        return TernaryFilter::make('direction')
            ->label(__('resources/correspondence/strings.filters.direction'))
            ->trueLabel(__('resources/correspondence/strings.direction.out'))
            ->falseLabel(__('resources/correspondence/strings.direction.in'))
            ->native(false)
            ->queries(
                true: fn (Builder $query) => $query->where('direction', Correspondence::DIRECTION_OUT),
                false: fn (Builder $query) => $query->where('direction', Correspondence::DIRECTION_IN),
            );
    }

    public static function directionGroup(): Group
    {
        return Group::make('direction')
            ->label(__('resources/correspondence/strings.filters.direction'))
            ->getTitleFromRecordUsing(fn (Model $record): string => __("resources/correspondence/strings.direction.{$record->direction}"))
            ->collapsible();
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/correspondence/strings.admin.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function number(): TextColumn
    {
        return TextColumn::make('number')
            ->label(__('resources/correspondence/strings.fields.number'))
            ->searchable()
            ->limit(30)
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function routeMode(): TextColumn
    {
        return TextColumn::make('route_mode')
            ->label(__('resources/correspondence/strings.admin.fields.route_mode'))
            ->badge()
            ->placeholder('—')
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.route_mode.{$state}"))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function senderCategory(): TextColumn
    {
        return TextColumn::make('sender_category')
            ->label(__('resources/correspondence/strings.admin.fields.sender_category'))
            ->badge()
            ->placeholder('—')
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.sender_category.{$state}"))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function sent(): TextColumn
    {
        return TextColumn::make('shared_at')
            ->label(__('resources/correspondence/strings.admin.fields.sent'))
            ->placeholder('—')
            ->formatStateUsing(fn ($state) => toJalali($state, 'Y/m/d H:i'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function signMode(): TextColumn
    {
        return TextColumn::make('sign_mode')
            ->label(__('resources/correspondence/strings.admin.fields.sign_mode'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.sign_mode.{$state}"))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function staleDraftFilter(): Filter
    {
        return Filter::make('stale_drafts')
            ->label(__('resources/correspondence/strings.filters.stale'))
            ->toggle()
            ->query(fn (Builder $query) => $query
                ->where('is_template', false)
                ->whereIn('status', Correspondence::DRAFT_LIKE)
                ->where('created_at', '<', now()->subDays(config('ai.correspondence.stale_draft_days'))));
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/correspondence/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.status.{$state}"))
            ->color(fn (string $state): string => Correspondence::STATUS_COLORS[$state] ?? 'info')
            ->icon(fn (string $state): string => Correspondence::STATUS_ICONS[$state] ?? 'heroicon-o-envelope')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/correspondence/strings.filters.status'))
            ->options(fn (): array => __('resources/correspondence/strings.status'));
    }

    public static function statusGroup(): Group
    {
        return Group::make('status')
            ->label(__('resources/correspondence/strings.fields.status'))
            ->getTitleFromRecordUsing(fn (Model $record): string => __("resources/correspondence/strings.status.{$record->status}"))
            ->collapsible();
    }

    public static function subject(): TextColumn
    {
        return TextColumn::make('subject')
            ->label(__('resources/correspondence/strings.fields.subject'))
            ->limit(40)
            ->placeholder('—')
            ->formatStateUsing(fn (string $state, Correspondence $record): string => $record->classification === Correspondence::CLASSIFICATION_NORMAL
                ? $state
                : __('resources/correspondence/strings.admin.masked'))
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function template(): IconColumn
    {
        return IconColumn::make('is_template')
            ->label(__('resources/correspondence/strings.filters.template'))
            ->boolean()
            ->trueIcon('heroicon-o-bookmark')
            ->falseIcon('heroicon-o-document')
            ->trueColor('info')
            ->falseColor('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function templateFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_template')
            ->label(__('resources/correspondence/strings.filters.template'))
            ->trueLabel(__('resources/correspondence/strings.admin.filters.templates_only'))
            ->falseLabel(__('resources/correspondence/strings.admin.filters.letters_only'))
            ->native(false);
    }

    public static function type(): TextColumn
    {
        return TextColumn::make('type')
            ->label(__('resources/correspondence/strings.fields.type'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.types.{$state}"))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function typeFilter(): SelectFilter
    {
        return SelectFilter::make('type')
            ->label(__('resources/correspondence/strings.filters.type'))
            ->options(fn (): array => __('resources/correspondence/strings.types'));
    }

    public static function urgency(): TextColumn
    {
        return TextColumn::make('urgency')
            ->label(__('resources/correspondence/strings.fields.urgency'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.urgency.{$state}"))
            ->color(fn (string $state): string => self::urgencyColor($state))
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
