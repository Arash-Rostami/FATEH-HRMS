<?php

namespace App\Filament\Resources\ReservationPolicyResource\Pages;

use App\Filament\Resources\ReservationPolicyResource;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;

class ListPolicies extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = ReservationPolicyResource::class;

    protected function listHeaderActions(): array
    {
        return [ReservationPolicyResource::setupGuideAction()];
    }
}