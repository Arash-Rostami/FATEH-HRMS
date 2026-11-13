<?php

namespace App\Filament\Pages;

use App\Traits\Filament\ExplainsHiddenRelationTabs;
use App\Traits\Filament\GuardsModuleLock;
use App\Traits\Filament\HandlesActionExceptions;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;

class ViewRecord extends BaseViewRecord
{
    use ExplainsHiddenRelationTabs;
    use GuardsModuleLock;
    use HandlesActionExceptions;

    protected function authorizeAccess(): void
    {
        $this->guardRecordLock();

        parent::authorizeAccess();
    }
}