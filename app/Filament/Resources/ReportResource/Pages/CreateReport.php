<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ReportResource::class;
}
