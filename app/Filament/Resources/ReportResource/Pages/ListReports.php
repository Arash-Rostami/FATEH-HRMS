<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReportResource::class;
}
