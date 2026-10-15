<?php

namespace App\Filament\RelationManagers;

use App\Filament\Traits\HandlesActionExceptions;
use App\Filament\Traits\HandlesDroppedFiles;
use Filament\Resources\RelationManagers\RelationManager as BaseRelationManager;

class RelationManager extends BaseRelationManager
{
    use HandlesActionExceptions;
    use HandlesDroppedFiles;
}