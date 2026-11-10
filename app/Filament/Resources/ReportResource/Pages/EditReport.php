<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\EditRecord;

class EditReport extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = ReportResource::class;

    protected function trackedFileColumns(): array
    {
        return ['cover_image' => [], 'file_path' => []];
    }
}
