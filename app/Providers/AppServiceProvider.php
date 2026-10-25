<?php

namespace App\Providers;

use App\Services\TenantService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Routing\Events\RouteMatched;
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

        Event::listen(Logout::class, fn(Logout $event) => $event->user?->markOffline());
        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            if (!config('app.off')) return;

            $name = (string)$event->route->getName();
            $names = str_ends_with($name, 'livewire.update')
                ? collect($event->request->input('components'))
                    ->pluck('snapshot')
                    ->filter(fn($s) => is_string($s))
                    ->map(fn($s) => json_decode($s, true)['memo']['name'] ?? '')
                    ->filter(fn($n) => is_string($n))->all()
                : [$name];

            foreach ($names as $n) {
                abort_if(tenantOffName($n), 404);
            }
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantService::class);
    }
}
