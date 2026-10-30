<?php

namespace App\Filament\Resources\NotifyRuleResource\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleFormPresenter;
use App\Traits\FilamentDateHandler;
use App\Traits\FilamentPageBehavior;

class CreateNotifyRule extends CreateRecord
{
    use FilamentDateHandler, FilamentPageBehavior;

    protected static string $resource = NotifyRuleResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'active_from', 'default_time' => '00:00'],
            ['field' => 'active_until', 'default_time' => '23:59'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return NotifyRuleFormPresenter::assertConditions(NotifyRuleFormPresenter::assertWindow($this->mergeDeadline($data)));
    }
}
