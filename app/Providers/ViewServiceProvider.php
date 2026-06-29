<?php

namespace App\Providers;

use App\Services\Menu\StateService as MenuStateService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer([
            'components.dashboard.modal.menu',
            'components.dashboard.navbars.right',
            'components.dashboard.navbars.bottom',
        ], function ($view) {
            $view->with('menuState', app(MenuStateService::class)->get());
        });

        View::composer('errors.layout', function ($view) {
            $view->with('trace_id', 'TRC-' . strtoupper(Str::random(8)));
        });
    }
}
