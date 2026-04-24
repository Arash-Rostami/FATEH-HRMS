<?php

namespace App\Traits;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait FilamentActions
{
    public static function assignAction(callable $handler): Action
    {
        return Action::make('assign')
            ->label(__('resources/general/strings.table.action_assign'))
            ->icon('heroicon-m-user-plus')
            ->color('success')
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_assign_heading'))
            ->modalDescription(__('resources/general/strings.table.action_assign_description'))
            ->action(function (Model $record) use ($handler): void {
                $handler($record);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.assigned'))
                    ->success()
                    ->send();
            });
    }

    public static function bulkActions(string $exporterClass): array
    {
        return [
            static::bulkDeleteAction(),
            static::bulkExportAction($exporterClass),
        ];
    }

    public static function bulkDeleteAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->label(__('resources/general/strings.table.bulk_delete'));
    }

    public static function bulkExportAction(string $exporterClass): ExportBulkAction
    {
        return ExportBulkAction::make()
            ->label(__('resources/general/strings.table.bulk_export'))
            ->exporter($exporterClass);
    }

    public static function bulkUnassignAction(callable $handler): BulkAction
    {
        return BulkAction::make('bulk_unassign')
            ->label(__('resources/general/strings.table.action_bulk_unassign'))
            ->icon('heroicon-m-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Collection $records) use ($handler): void {
                $records->each($handler);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.bulk_unassigned'))
                    ->success()
                    ->send();
            });
    }

    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip(__('resources/general/strings.table.action_delete'))
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_delete_confirm'))
            ->modalDescription(__('resources/general/strings.table.action_delete_body'));
    }

    public static function editAction(): EditAction
    {
        return EditAction::make()
            ->tooltip(__('resources/general/strings.table.action_edit'))
            ->iconButton();
    }

    public static function unassignAction(callable $handler): Action
    {
        return Action::make('unassign')
            ->label(__('resources/general/strings.table.action_unassign'))
            ->icon('heroicon-m-user-minus')
            ->color('danger')
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(__('resources/general/strings.table.action_unassign_heading'))
            ->modalDescription(__('resources/general/strings.table.action_unassign_description'))
            ->action(function (Model $record) use ($handler): void {
                $handler($record);
                Notification::make()
                    ->title(__('resources/general/strings.notifications.unassigned'))
                    ->success()
                    ->send();
            });
    }

    public static function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->tooltip(__('resources/general/strings.table.action_view'))
            ->iconButton()
            ->slideOver();
    }
}
