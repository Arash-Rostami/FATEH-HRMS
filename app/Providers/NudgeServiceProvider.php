<?php

namespace App\Providers;

use App\Services\Menu\Notifications\ActiveAdsNudge;
use App\Services\Menu\Notifications\ChannelInviteNudge;
use App\Services\Menu\Notifications\ContactNudge;
use App\Services\Menu\Notifications\DmsNudge;
use App\Services\Menu\Notifications\FeedNudge;
use App\Services\Menu\Notifications\PhotoNudge;
use App\Services\Menu\Notifications\PostNudge;
use App\Services\Menu\Notifications\ReportNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\Menu\Notifications\ThsNudge;
use App\Services\Menu\NudgeService;
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
        NudgeService::register(new FeedNudge());
        NudgeService::register(new TaskNudge());
        NudgeService::register(new ContactNudge());
        NudgeService::register(new ThsNudge());
        NudgeService::register(new DmsNudge());
        NudgeService::register(new ChannelInviteNudge());
    }
}
