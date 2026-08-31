<?php

namespace App\Providers;

use App\Services\Menu\EdgeService;
use App\Services\Menu\Toasts\ChannelToast;
use App\Services\Menu\Toasts\ProjectToast;
use App\Services\Menu\Toasts\TaskDueSoonToast;
use Illuminate\Support\ServiceProvider;

class EdgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        EdgeService::register(new ChannelToast());
        EdgeService::register(new ProjectToast());
        EdgeService::register(new TaskDueSoonToast());
    }
}