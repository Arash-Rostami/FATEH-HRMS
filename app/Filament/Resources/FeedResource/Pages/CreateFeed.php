<?php

namespace App\Filament\Resources\FeedResource\Pages;

use App\Filament\Resources\FeedResource;
use App\Filament\Resources\FeedResource\Schemas\FeedFormPresenter;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateFeed extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = FeedResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return FeedFormPresenter::mergeMediaPaths($data);
    }
}
