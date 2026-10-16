<?php

namespace App\Traits;

use App\Models\Department;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait FilamentVisibility
{
    public static function activeColumn(): ToggleColumn
    {
        return ToggleColumn::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->onIcon('heroicon-m-check-circle')
            ->offIcon('heroicon-m-x-circle')
            ->onColor('success')
            ->offColor('danger')
            ->disabled(fn() => !static::canEditAny())
            ->toggleable(isToggledHiddenByDefault: false);
    }

    public static function activeEntry(): IconEntry
    {
        return IconEntry::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger');
    }

    public static function activeExportColumn(): ExportColumn
    {
        return ExportColumn::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->formatStateUsing(fn($state): string => $state
                ? __('resources/general/strings.visibility.status_active')
                : __('resources/general/strings.visibility.status_inactive'));
    }

    public static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->trueLabel(__('resources/general/strings.visibility.status_active'))
            ->falseLabel(__('resources/general/strings.visibility.status_inactive'))
            ->native(false);
    }

    public static function activeGroup(): Group
    {
        return Group::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->getTitleFromRecordUsing(fn($record): string => $record->active
                ? __('resources/general/strings.visibility.status_active')
                : __('resources/general/strings.visibility.status_inactive'))
            ->collapsible();
    }

    public static function activeTabs(Closure $stats): array
    {
        return [
            'active' => Tab::make(__('resources/general/strings.visibility.status_active'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $stats()->active_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', true)),

            'inactive' => Tab::make(__('resources/general/strings.visibility.status_inactive'))
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $stats()->inactive_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', false)),
        ];
    }

    public static function activeToggle(): Toggle
    {
        return Toggle::make('active')
            ->label(__('resources/general/strings.visibility.active'))
            ->default(true)
            ->inline(false)
            ->helperText(__('resources/general/strings.visibility.active_hint'));
    }

    public static function audienceColumn(): TextColumn
    {
        return TextColumn::make('audience')
            ->label(__('resources/general/strings.visibility.audience'))
            ->placeholder(__('resources/general/strings.visibility.public'))
            ->badge()
            ->color(fn($record) => $record->is_public ? 'success' : ($record->audience_departments->count() > 1 ? 'info' : 'warning'))
            ->icon(fn($record) => $record->is_public
                ? 'heroicon-o-globe-alt'
                : ($record->audience_departments->count() > 1 ? 'heroicon-o-users' : 'heroicon-o-lock-closed'))
            ->getStateUsing(function ($record) {
                $labels = $record->audience_departments->map(fn($d) => $d->displayLabel());
                if ($labels->isEmpty()) {
                    return null;
                }
                return $labels->count() > 2
                    ? $labels->take(2)->join('، ') . __('resources/general/strings.visibility.more', ['count' => convertToPersian($labels->count() - 2)])
                    : $labels->join('، ');
            })
            ->tooltip(fn($record) => $record->audience_departments->isEmpty()
                ? null
                : $record->audience_departments->map(fn($d) => $d->tooltipLabel())->join('، '))
            ->sortable(false)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function audienceEntry(): TextEntry
    {
        return TextEntry::make('audience')
            ->label(__('resources/general/strings.visibility.audience'))
            ->placeholder(__('resources/general/strings.visibility.public'))
            ->badge()
            ->color(fn($record) => $record->is_public ? 'success' : ($record->audience_departments->count() > 1 ? 'info' : 'warning'))
            ->icon(fn($record) => $record->is_public
                ? 'heroicon-o-globe-alt'
                : ($record->audience_departments->count() > 1 ? 'heroicon-o-users' : 'heroicon-o-lock-closed'))
            ->getStateUsing(fn($record) => $record->audience_departments->isEmpty()
                ? null
                : $record->audience_departments->map(fn($d) => $d->displayLabel())->join('، '));
    }

    public static function audienceExportColumn(): ExportColumn
    {
        return ExportColumn::make('audience')
            ->label(__('resources/general/strings.visibility.audience'))
            ->state(fn($record) => $record->audience_departments->isEmpty()
                ? __('resources/general/strings.visibility.public')
                : $record->audience_departments->map(fn($d) => $d->displayLabel())->join('، '));
    }

    public static function audienceFilter(): SelectFilter
    {
        return SelectFilter::make('audience')
            ->label(__('resources/general/strings.visibility.departments'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->query(fn(Builder $query, array $data) => $query
                ->when($data['value'] ?? null, fn($q, $value) => $q->whereJsonContains('departments', $value)));
    }

    public static function bulkActivateAction(): BulkAction
    {
        return static::bulkActiveAction(true);
    }

    public static function bulkDeactivateAction(): BulkAction
    {
        return static::bulkActiveAction(false);
    }

    public static function bulkVisibilityActions(): array
    {
        return [static::bulkActivateAction(), static::bulkDeactivateAction()];
    }

    public static function departmentsSelect(): Select
    {
        return Select::make('departments')
            ->label(__('resources/general/strings.visibility.departments'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->multiple()
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/general/strings.visibility.departments_hint'));
    }

    public static function visibilityFilter(): TernaryFilter
    {
        return TernaryFilter::make('visibility')
            ->label(__('resources/general/strings.visibility.audience'))
            ->trueLabel(__('resources/general/strings.visibility.restricted'))
            ->falseLabel(__('resources/general/strings.visibility.public'))
            ->native(false)
            ->queries(
                true: fn(Builder $q) => $q->whereNotNull('departments')->whereRaw('JSON_LENGTH(departments) > 0'),
                false: fn(Builder $q) => $q->where(fn($x) => $x->whereNull('departments')->orWhereRaw('JSON_LENGTH(departments) = 0')),
            );
    }

    private static function bulkActiveAction(bool $active): BulkAction
    {
        $key = $active ? 'activate' : 'deactivate';

        return BulkAction::make("bulk_{$key}")
            ->label(__("resources/general/strings.visibility.bulk_{$key}"))
            ->icon($active ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
            ->color($active ? 'success' : 'gray')
            ->visible(fn() => static::canEditAny())
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($active, $key): void {
                $records->each->update(['active' => $active]);
                Notification::make()
                    ->title(__("resources/general/strings.visibility.{$key}d"))
                    ->success()
                    ->send();
            });
    }
}
