<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = DepartmentResource::class;

}
