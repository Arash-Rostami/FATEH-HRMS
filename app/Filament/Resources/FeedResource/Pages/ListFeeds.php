<?php

namespace App\Filament\Resources\FeedResource\Pages;

use App\Filament\Resources\FeedResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListFeeds extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = FeedResource::class;
}
