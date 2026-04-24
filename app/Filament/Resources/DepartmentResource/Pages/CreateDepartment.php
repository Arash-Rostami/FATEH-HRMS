<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = DepartmentResource::class;

}
