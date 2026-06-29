<?php

namespace App\Providers;

use App\Services\Menu\NudgeService;
use App\Services\Menu\Notifications\ActiveAdsNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use Illuminate\Support\ServiceProvider;

class NudgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NudgeService::register(new ActiveAdsNudge());
        NudgeService::register(new SharedEventsNudge());
        NudgeService::register(new SuggestionNudge());
    }
}