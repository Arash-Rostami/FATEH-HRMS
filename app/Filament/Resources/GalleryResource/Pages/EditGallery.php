<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditGallery extends EditRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = GalleryResource::class;
}
