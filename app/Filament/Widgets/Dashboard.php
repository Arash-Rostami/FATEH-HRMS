<?php

namespace App\Filament\Widgets;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string|Htmlable
    {
        return view('filament.widgets.dashboard');
    }
}
