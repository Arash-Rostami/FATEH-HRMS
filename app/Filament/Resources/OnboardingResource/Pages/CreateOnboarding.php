<?php

namespace App\Filament\Resources\OnboardingResource\Pages;

use App\Filament\Resources\OnboardingResource;
use App\Filament\Resources\OnboardingResource\Pages\Action\EnforcesActiveOnboarding;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingFormPresenter;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Filament\Resources\Pages\CreateRecord;

class CreateOnboarding extends CreateRecord
{
    use FilamentHeaderActions, FilamentPageBehavior;

    protected static string $resource = OnboardingResource::class;

    protected function afterCreate(): void
    {
        EnforcesActiveOnboarding::deactivateConflicting($this->record);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OnboardingFormPresenter::hydrateGuidesWithFileMeta($data);
    }
}
