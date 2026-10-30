<?php

namespace App\Filament\Resources\NotifyRuleResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\Action\SendMessageAction;
use App\Filament\Resources\NotifyRuleResource\Schemas\Action\ViewSwitchActions;
use App\Traits\FilamentHeaderActions;

class ListNotifyRules extends ListRecords
{
    use FilamentHeaderActions {
        getHeaderActions as private baseHeaderActions;
    }

    protected static string $resource = NotifyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [...ViewSwitchActions::make('rules'), SendMessageAction::make(), ...$this->baseHeaderActions()];
    }
}
