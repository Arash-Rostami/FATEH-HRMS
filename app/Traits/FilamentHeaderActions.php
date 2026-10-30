<?php

namespace App\Traits;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\SoftDeletes;

trait FilamentHeaderActions
{
    protected function getHeaderActions(): array
    {
        return match (true) {
            $this instanceof ListRecords => $this->listHeaderActions(),
            $this instanceof EditRecord => $this->editHeaderActions(),
            default => [],
        };
    }

    private function editHeaderActions(): array
    {
        $actions = [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
            ViewAction::make()
                ->icon('heroicon-o-eye')
                ->label(__('resources/general/strings.table.action_view')),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->label(__('resources/general/strings.table.action_delete')),
        ];

        if (in_array(SoftDeletes::class, class_uses_recursive(static::getResource()::getModel()), true)) {
            $actions[] = RestoreAction::make()
                ->icon('heroicon-o-arrow-uturn-left')
                ->label(__('resources/general/strings.table.action_restore'));
        }

        return $actions;
    }

    private function listHeaderActions(): array
    {
        $actions = [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
        ];

        $resource = static::getResource();
        if (method_exists($resource, 'guideTabs') && !empty($resource::guideTabs())) {
            array_unshift($actions, $resource::setupGuideAction());
        }

        return $actions;
    }
}
