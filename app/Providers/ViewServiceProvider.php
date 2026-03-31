<?php

namespace App\Providers;

use App\Models\Ad;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('components.dashboard.modal.menu', function ($view) {
            $view->with('menuState', cache()->remember('menu_state', now()->addHours(2), function () {
                return [
                    'ads-controller' => Ad::active()->exists(),
                ];
            }));
        });
    }
}
