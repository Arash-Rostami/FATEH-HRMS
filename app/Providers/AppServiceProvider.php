<?php

namespace App\Providers;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/migrated'));

        Event::listen(Logout::class, fn (Logout $event) => $event->user?->markOffline());
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
