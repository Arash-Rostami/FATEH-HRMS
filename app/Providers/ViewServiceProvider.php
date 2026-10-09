<?php

namespace App\Providers;

use App\View\Composers\AccountOnboardingComposer;
use App\View\Composers\ErrorTraceComposer;
use App\View\Composers\MenuStateComposer;
use App\View\Composers\ModuleAssetChunksComposer;
use App\View\Composers\ModuleChunkComposer;
use App\View\Composers\OccasionComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts::app'], ModuleChunkComposer::class);

        View::composer([
            'components.dashboard.modal.menu',
            'components.dashboard.navbars.right',
            'components.dashboard.navbars.bottom',
            'components.dashboard.navbars.top',
        ], MenuStateComposer::class);

        View::composer('errors.layout', ErrorTraceComposer::class);

        View::composer('components.dashboard.navbars.top.account', AccountOnboardingComposer::class);

        View::composer('components.dashboard.modal.occasion', OccasionComposer::class);

        View::composer('components.dashboard.module-chunks', ModuleAssetChunksComposer::class);
    }
}
