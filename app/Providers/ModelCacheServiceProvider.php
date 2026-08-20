<?php

namespace App\Providers;

use App\Services\Cache\ModelCacheVersion;
use App\Services\Cache\SkipsAutomaticCacheVersioning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ModelCacheServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen('eloquent.saved: *', fn(string $event, array $data) => $this->bump($data[0]));
        Event::listen('eloquent.deleted: *', fn(string $event, array $data) => $this->bump($data[0]));
        Event::listen('eloquent.restored: *', fn(string $event, array $data) => $this->bump($data[0]));
    }

    private function bump(Model $model): void
    {
        if ($model instanceof SkipsAutomaticCacheVersioning) {
            return;
        }

        ModelCacheVersion::bump(get_class($model));
    }
}
