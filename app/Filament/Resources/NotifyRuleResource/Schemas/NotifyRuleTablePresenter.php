<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas;

use App\Enums\NotifyEvent;
use App\Filament\Resources\NotifyRuleResource;
use App\Models\Department;
use App\Models\NotifyRule;
use App\Services\Notify\NotifyConditions;
use App\Services\Notify\NotifyRegistry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NotifyRuleTablePresenter
{
    public static function channels(): TextColumn
    {
        return TextColumn::make('channels')
            ->label(__('resources/notify_rule/strings.fields.channels'))
            ->badge()
            ->formatStateUsing(fn ($state) => NotifyRegistry::channel((string) $state)?->label() ?? $state)
            ->color('info')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function conditions(): TextColumn
    {
        return TextColumn::make('conditions')
            ->label(__('resources/notify_rule/strings.fields.conditions'))
            ->badge()
            ->getStateUsing(fn (Model $record) => ($count = count($record->conditions['rules'] ?? []))
                ? __('resources/notify_rule/strings.conditions.count', ['count' => $count])
                : '-')
            ->tooltip(fn (Model $record) => implode("\n", NotifyConditions::summary((string) $record->model, $record->conditions)) ?: null)
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/notify_rule/strings.fields.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function events(): TextColumn
    {
        return TextColumn::make('events')
            ->label(__('resources/notify_rule/strings.fields.events'))
            ->badge()
            ->formatStateUsing(fn ($state) => NotifyEvent::tryFrom((string) $state)?->label() ?? $state)
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label(__('resources/notify_rule/strings.fields.id'))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isActive(): ToggleColumn
    {
        return ToggleColumn::make('is_active')
            ->label(__('resources/notify_rule/strings.fields.is_active'))
            ->onIcon('heroicon-m-check-circle')
            ->offIcon('heroicon-m-x-circle')
            ->onColor('success')
            ->offColor('danger')
            ->disabled(fn (Model $record) => ! NotifyRuleResource::canEdit($record))
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function isActiveFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label(__('resources/notify_rule/strings.filters.is_active'));
    }

    public static function liveFilter(): TernaryFilter
    {
        return TernaryFilter::make('live')
            ->label(__('resources/notify_rule/strings.filters.live'))
            ->queries(
                true: fn (Builder $query) => $query->live(),
                false: fn (Builder $query) => $query->whereNot(fn (Builder $q) => $q->live()),
            );
    }

    public static function logsCount(): TextColumn
    {
        return TextColumn::make('logs_count')
            ->label(__('resources/notify_rule/strings.fields.logs_count'))
            ->numeric()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function model(): TextColumn
    {
        return TextColumn::make('model')
            ->label(__('resources/notify_rule/strings.fields.model'))
            ->badge()
            ->formatStateUsing(fn ($state) => $state === NotifyRule::ONE_OFF
                ? __('resources/notify_rule/strings.manual.label')
                : NotifyRegistry::trackables()[$state] ?? class_basename((string) $state))
            ->color(fn ($state) => $state === NotifyRule::ONE_OFF ? 'warning' : 'primary')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function modelFilter(): SelectFilter
    {
        return SelectFilter::make('model')
            ->label(__('resources/notify_rule/strings.filters.model'))
            ->options(fn () => NotifyRegistry::trackables() + [NotifyRule::ONE_OFF => __('resources/notify_rule/strings.manual.label')]);
    }

    public static function name(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('resources/notify_rule/strings.fields.name'))
            ->limit(60)
            ->tooltip(fn ($state) => strlen((string) $state) > 60 ? $state : null)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function recipients(): TextColumn
    {
        return TextColumn::make('recipients')
            ->label(__('resources/notify_rule/strings.fields.recipients'))
            ->getStateUsing(function (Model $record) {
                $audience = $record->extra['audience'] ?? 'users';

                return $audience === 'users'
                    ? count($record->user_ids ?? []) + count($record->extra['contacts'] ?? [])
                    : __("resources/notify_rule/strings.audience.{$audience}");
            })
            ->tooltip(fn (Model $record) => ($record->extra['audience'] ?? 'users') === 'departments'
                ? Department::getCachedOptions()->only($record->extra['departments'] ?? [])->implode("\n") ?: null
                : null)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function window(): TextColumn
    {
        return TextColumn::make('window')
            ->label(__('resources/notify_rule/strings.fields.window'))
            ->getStateUsing(function (Model $record): string {
                if (! $record->active_from && ! $record->active_until) {
                    return '-';
                }

                $from = $record->active_from ? toJalali($record->active_from, 'Y/m/d H:i') : '…';
                $until = $record->active_until ? toJalali($record->active_until, 'Y/m/d H:i') : '…';

                return "{$from} → {$until}";
            })
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
