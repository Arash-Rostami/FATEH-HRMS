<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectTask\ProjectSettings;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use App\Filament\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateProject extends CreateRecord
{
    use FilamentHeaderActions;
    use FilamentPageBehavior;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Project::generateSlug($data['name'] ?? '');

        $rows = Arr::pull($data, 'extraSettings', []);
        $settings = app(ProjectSettings::class)->mergeExtra([], $rows);
        $data['settings'] = array_merge($settings, $data['settings'] ?? []);

        return $data;
    }
}
