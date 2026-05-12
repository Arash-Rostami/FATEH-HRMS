<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Traits\{FilamentHeaderActions, FilamentPageBehavior};
use Filament\Resources\Pages\{CreateRecord, EditRecord, ListRecords};

class ListGalleries extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = GalleryResource::class;
}

class CreateGallery extends CreateRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = GalleryResource::class;
}

class EditGallery extends EditRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = GalleryResource::class;
}
