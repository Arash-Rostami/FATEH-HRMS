<?php

namespace App\Filament\Resources\CorrespondenceResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\CorrespondenceResource;
use App\Traits\Filament\FilamentHeaderActions;

class ListCorrespondences extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = CorrespondenceResource::class;

    protected function listHeaderActions(): array
    {
        return [...CorrespondenceResource::switchActions(static::$resource), CorrespondenceResource::setupGuideAction()];
    }
}
