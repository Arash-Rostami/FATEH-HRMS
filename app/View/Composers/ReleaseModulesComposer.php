<?php

namespace App\View\Composers;

use Illuminate\View\View;

class ReleaseModulesComposer
{
    public function compose(View $view): void
    {
        $view->with('allModules', collect(config('modules', []))->reject(fn ($module) => tenantOff($module['id'])));
    }
}
