<?php

namespace App\Filament\Resources\FeedResource\Pages;

use App\Filament\Resources\FeedResource;
use App\Filament\Resources\FeedResource\Schemas\FeedFormPresenter;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\EditRecord;

class EditFeed extends EditRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = FeedResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return FeedFormPresenter::unpackPollSettings(FeedFormPresenter::splitMediaPaths($data));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return FeedFormPresenter::packPollSettings(FeedFormPresenter::mergeMediaPaths($data));
    }
}
