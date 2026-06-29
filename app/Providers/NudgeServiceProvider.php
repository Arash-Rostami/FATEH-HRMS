<?php

namespace App\Providers;

use App\Services\Menu\NudgeService;
use App\Services\Menu\Notifications\ActiveAdsNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use App\Services\Menu\Notifications\PostNudge;
use App\Services\Menu\Notifications\PhotoNudge;
use App\Services\Menu\Notifications\ReportNudge;


use Illuminate\Support\ServiceProvider;

class NudgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NudgeService::register(new ActiveAdsNudge());
        NudgeService::register(new SharedEventsNudge());
        NudgeService::register(new SuggestionNudge());
        NudgeService::register(new PostNudge());
        NudgeService::register(new PhotoNudge());
        NudgeService::register(new ReportNudge());


    }
}