<?php

namespace App\Providers;

use App\Services\MenuStateService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('components.dashboard.modal.menu', function ($view) {
            $view->with('menuState', app(MenuStateService::class)->get());
        });
    }
}
