<?php

namespace App\Providers;

use App\Services\Menu\Notifications\ActiveAdsNudge;
use App\Services\Menu\Notifications\ContactNudge;
use App\Services\Menu\Notifications\FeedNudge;
use App\Services\Menu\Notifications\PhotoNudge;
use App\Services\Menu\Notifications\PostNudge;
use App\Services\Menu\Notifications\ReportNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\Menu\NudgeService;
use Illuminate\Support\ServiceProvider;
use App\Services\Menu\Notifications\EnergyTestNudge;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;


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
        NudgeService::register(new FeedNudge());
        NudgeService::register(new TaskNudge());
        NudgeService::register(new ContactNudge());
        NudgeService::register(new EnergyTestNudge());

        Event::listen(Login::class, function (Login $event) {
            dispatch(new \App\Jobs\ReconcileNudge('energy-controller:nudge', \App\Models\User::class, $event->user->id))->afterCommit();
        });


    }
}
