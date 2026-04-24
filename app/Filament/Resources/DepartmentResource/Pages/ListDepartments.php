<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = DepartmentResource::class;
}
