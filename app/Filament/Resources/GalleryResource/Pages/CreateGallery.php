<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    use  FilamentPageBehavior;

    protected static string $resource = GalleryResource::class;
}
