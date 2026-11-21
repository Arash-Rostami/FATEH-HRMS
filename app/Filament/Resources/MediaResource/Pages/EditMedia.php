<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Pages\EditRecord;
use App\Filament\Resources\MediaResource;
use App\Traits\Filament\FilamentEditHeading;
use App\Traits\Filament\FilamentHeaderActions;
use App\Traits\Filament\FilamentPageBehavior;

class EditMedia extends EditRecord
{
    use FilamentEditHeading, FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = MediaResource::class;

    protected function trackedFileColumns(): array
    {
        return ['cover_image' => [], 'file_path' => [], 'transcript_path' => [], 'chapters_path' => []];
    }
}
