<?php

namespace App\Providers;

use App\View\Composers\AccountOnboardingComposer;
use App\View\Composers\AiAssistantComposer;
use App\View\Composers\AuthShellComposer;
use App\View\Composers\ChatFontComposer;
use App\View\Composers\ErrorTraceComposer;
use App\View\Composers\LeftNavComposer;
use App\View\Composers\MenuStateComposer;
use App\View\Composers\ModuleAssetChunksComposer;
use App\View\Composers\ModuleChunkComposer;
use App\View\Composers\NavUnreadComposer;
use App\View\Composers\OccasionComposer;
use App\View\Composers\ReleaseModulesComposer;
use App\View\Composers\ScreenSaverComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    private const COMPOSERS = [
        ModuleChunkComposer::class => ['layouts.app', 'layouts::app'],
        MenuStateComposer::class => ['components.dashboard.modal.menu', 'components.dashboard.navbars.right', 'components.dashboard.navbars.bottom', 'components.dashboard.navbars.top',],
        ErrorTraceComposer::class => 'errors.layout',
        AccountOnboardingComposer::class => 'components.dashboard.navbars.top.account',
        OccasionComposer::class => 'components.dashboard.modal.occasion',
        ModuleAssetChunksComposer::class => 'components.dashboard.module-chunks',
        ScreenSaverComposer::class => 'components.ui.loaders.screen-saver',
        AiAssistantComposer::class => ['components.dashboard.ai-assistant', 'components.dashboard.navbars.top.ai-assistant',],
        LeftNavComposer::class => 'components.dashboard.navbars.left',
        ReleaseModulesComposer::class => 'components.dashboard.modal.release',
        AuthShellComposer::class => ['layouts.auth', 'layouts::auth', 'components.auth.card-no-signup'],
        NavUnreadComposer::class => ['components.dashboard.navbars.top', 'components.dashboard.navbars.top.notification',],
        ChatFontComposer::class => 'livewire.dashboard.messaging.font-picker',
    ];

    public function boot(): void
    {
        foreach (self::COMPOSERS as $composer => $views) {
            View::composer($views, $composer);
        }
    }
}
