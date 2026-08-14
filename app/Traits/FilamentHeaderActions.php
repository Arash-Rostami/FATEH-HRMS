<?php

namespace App\Traits;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

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
        return [
            CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->label(__('resources/general/strings.table.action_create')),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->label(__('resources/general/strings.table.action_delete')),
        ];
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
