<?php

namespace App\Filament\Resources\NotifyRuleResource\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\Action\SendTestAction;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleFormPresenter;
use App\Traits\FilamentDateHandler;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;

class EditNotifyRule extends EditRecord
{
    use FilamentDateHandler, FilamentEditHeading, FilamentPageBehavior;
    use FilamentHeaderActions {
        getHeaderActions as private baseHeaderActions;
    }

    protected static string $resource = NotifyRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [SendTestAction::make('mail'), SendTestAction::make('sms'), ...$this->baseHeaderActions()];
    }

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'active_from', 'default_time' => '00:00'],
            ['field' => 'active_until', 'default_time' => '23:59'],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        return NotifyRuleFormPresenter::assertConditions(NotifyRuleFormPresenter::assertWindow(
            NotifyRuleFormPresenter::mergeExtra($this->mergeDeadline($data), $record->extra)
        ));
    }
}
