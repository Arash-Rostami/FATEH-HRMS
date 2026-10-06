<?php

namespace App\View\Composers;

use App\Services\Menu\StateService as MenuStateService;
use Illuminate\View\View;

class MenuStateComposer
{
    public function compose(View $view): void
    {
        $view->with('menuState', app(MenuStateService::class)->get());
    }
}
