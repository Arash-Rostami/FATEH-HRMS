<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = PostResource::class;
}
