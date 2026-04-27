<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ReportResource::class;
}
