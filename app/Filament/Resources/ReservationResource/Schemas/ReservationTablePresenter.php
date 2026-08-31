<?php

namespace App\Filament\Resources\ReservationResource\Schemas;

use App\Enums\CancelReason;
use App\Enums\ReservationStatus;
use App\Enums\ResourceType;
use App\Livewire\Dashboard\Reservation\Actions\CancelAction;
use App\Models\Reservation;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class ReservationTablePresenter
{
    public static function byResource(): Group
    {
        return Group::make('resource.name')
            ->label(__('resources/reservation/strings.fields.resource'))
            ->getTitleFromRecordUsing(fn($record): string => $record->resource?->labeled_name ?? '—')
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function byStatus(): Group
    {
        return Group::make('status')
            ->label(__('resources/reservation/strings.fields.status'))
            ->getTitleFromRecordUsing(
                fn($record): string => ReservationStatus::tryFrom($record?->status)?->getLabel() ?? (string)$record?->status
            )
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function byUser(): Group
    {
        return Group::make('user.name')
            ->label(__('resources/reservation/strings.fields.user'))
            ->getTitleFromRecordUsing(fn($record): string => $record->user?->name ?? '—')
            ->titlePrefixedWithLabel(false)
            ->collapsible();
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('resources/reservation/strings.actions.cancel'))
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->iconButton()
            ->visible(fn(Reservation $record) => $record->status === 'active')
            ->schema([
                Select::make('cancel_reason')
                    ->label(__('resources/reservation/strings.fields.cancel_reason'))
                    ->options(CancelReason::class)
                    ->nullable()
                    ->native(false)
                    ->placeholder(__('resources/reservation/strings.fields.cancel_reason_placeholder'))
            ])
            ->requiresConfirmation()
            ->modalHeading(__('resources/reservation/strings.actions.cancel_confirm'))
            ->action(function (Reservation $record, array $data): void {
                $reason = $data['cancel_reason'] instanceof CancelReason
                    ? $data['cancel_reason']->value
                    : ($data['cancel_reason'] ?? null);

                app(CancelAction::class)->execute($record, auth()->user(), $reason);

                Notification::make()
                    ->title(__('resources/reservation/strings.actions.cancel_success'))
                    ->success()
                    ->send();
            });
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/reservation/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->sortable()
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->label('ID')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function isFullDay(): TextColumn
    {
        return TextColumn::make('is_full_day')
            ->label(__('resources/reservation/strings.fields.is_full_day'))
            ->badge()
            ->formatStateUsing(fn($state) => (bool)$state ? 'تمام‌روز' : 'ساعتی')
            ->color(fn($state) => (bool)$state ? 'info' : 'gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function isFullDayFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_full_day')
            ->label(__('resources/reservation/strings.filters.is_full_day'));
    }

    public static function isSeries(): TextColumn
    {
        return TextColumn::make('parent_id')
            ->label('سری')
            ->badge()
            ->formatStateUsing(fn($state) => $state !== null ? 'تکراری' : 'مستقل')
            ->color(fn($state) => $state !== null ? 'warning' : 'gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function isSeriesFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_series')
            ->label(__('resources/reservation/strings.filters.is_series'))
            ->queries(
                true: fn(Builder $query) => $query->whereNotNull('parent_id'),
                false: fn(Builder $query) => $query->whereNull('parent_id'),
            );
    }

    public static function releaseAction(): Action
    {
        return Action::make('release')
            ->label(__('resources/reservation/strings.actions.release'))
            ->icon('heroicon-m-arrow-path')
            ->color('info')
            ->iconButton()
            ->visible(fn(Reservation $record) => $record->status === 'active')
            ->requiresConfirmation()
            ->modalHeading(__('resources/reservation/strings.actions.release_confirm'))
            ->action(function (Reservation $record): void {
                if ($record->isRange() && $record->start_time?->isPast()) {
                    $record->update(['end_time' => now(), 'status' => 'released']);
                } else {
                    $record->update(['status' => 'released']);
                }
                Notification::make()
                    ->title(__('resources/reservation/strings.actions.release_success'))
                    ->success()
                    ->send();
            });
    }

    public static function resource(): TextColumn
    {
        return TextColumn::make('resource.labeled_name')
            ->label(__('resources/reservation/strings.fields.resource'))
            ->default('—')
            ->sortable()
            ->searchable(['name'])
            ->toggleable();
    }

    public static function resourceTypeFilter(): SelectFilter
    {
        return SelectFilter::make('resource_type')
            ->label(__('resources/reservation/strings.filters.resource_type'))
            ->options(collect(ResourceType::cases())->mapWithKeys(fn(ResourceType $t) => [$t->value => $t->getLabel()])->all())
            ->query(fn(Builder $query, array $data) => $query->when(
                $data['value'] ?? null,
                fn($q, $v) => $q->whereHas('resource', fn($q) => $q->where('type', $v))
            ));
    }

    public static function startTime(): TextColumn
    {
        return TextColumn::make('start_time')
            ->label(__('resources/reservation/strings.fields.start_time'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->icon('heroicon-m-clock')
            ->iconPosition(IconPosition::After)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function endTime(): TextColumn
    {
        return TextColumn::make('end_time')
            ->label(__('resources/reservation/strings.fields.end_time'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->icon('heroicon-m-clock')
            ->iconPosition(IconPosition::After)
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/reservation/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn($state) => $state
                ? (ReservationStatus::tryFrom($state)?->getLabel() ?? $state)
                : '—'
            )
            ->color(fn($state) => $state
                ? (ReservationStatus::tryFrom($state)?->getColor() ?? 'gray')
                : 'gray'
            )
            ->icon(fn($state) => $state
                ? (ReservationStatus::tryFrom($state)?->getIcon())
                : null
            )
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/reservation/strings.filters.status'))
            ->options(ReservationStatus::class);
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label(__('resources/reservation/strings.fields.user'))
            ->default('—')
            ->sortable()
            ->searchable()
            ->weight(FontWeight::Medium)
            ->toggleable(isToggledHiddenByDefault: false);
    }
}
