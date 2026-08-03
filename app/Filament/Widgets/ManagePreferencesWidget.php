<?php

namespace App\Filament\Widgets;

use App\Traits\FilamentPreferences;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class ManagePreferencesWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use FilamentPreferences;

    protected static ?int $sort = -2;

    protected string $view = 'livewire.admin.widgets.filament-preferences';

    protected int | string | array $columnSpan = 'full';

    protected function getPreferencesColumns(): int
    {
        return 4;
    }
}
