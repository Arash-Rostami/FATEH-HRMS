<?php

namespace App\View\Composers;

use Illuminate\View\View;

class AuthShellComposer
{
    public function compose(View $view): void
    {
        $view->with(str_starts_with($view->name(), 'components.') ? $this->cardData() : $this->layoutData());
    }

    private function layoutData(): array
    {
        return [
            'videos' => collect(config('app.videos', []))->map(fn ($path) => asset($path))->values()->all(),
        ];
    }

    private function cardData(): array
    {
        return [
            'lightLogo' => asset(tenantLogo(false, 'user', true)),
            'darkLogo' => asset(tenantLogo(true, 'user', true)),
            'appName' => config('app.name_en', 'WinterrA'),
        ];
    }
}
