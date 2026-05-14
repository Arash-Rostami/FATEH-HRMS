<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListGallery extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = GalleryResource::class;
}
