<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\Filament\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    use  FilamentPageBehavior;

    protected static string $resource = GalleryResource::class;
}
