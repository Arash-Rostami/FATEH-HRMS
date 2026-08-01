<?php

namespace App\Filament\Resources\ReleaseRequestResource\Pages;

use App\Filament\Resources\ReleaseRequestResource;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReleaseRequest extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ReleaseRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}