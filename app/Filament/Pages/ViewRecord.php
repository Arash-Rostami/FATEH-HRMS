<?php

namespace App\Filament\Pages;

use App\Traits\Filament\ExplainsHiddenRelationTabs;
use App\Traits\Filament\HandlesActionExceptions;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;

class ViewRecord extends BaseViewRecord
{
    use ExplainsHiddenRelationTabs;
    use HandlesActionExceptions;
}