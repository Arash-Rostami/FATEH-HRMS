<?php

namespace App\Providers;

use App\Services\Menu\Notifications\AdNudge;
use App\Services\Menu\Notifications\ChannelNudge;
use App\Services\Menu\Notifications\ContactNudge;
use App\Services\Menu\Notifications\DmsNudge;
use App\Services\Menu\Notifications\FeedNudge;
use App\Services\Menu\Notifications\PhotoNudge;
use App\Services\Menu\Notifications\PostNudge;
use App\Services\Menu\Notifications\ProjectNudge;
use App\Services\Menu\Notifications\ReminderOverdueNudge;
use App\Services\Menu\Notifications\ReportNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\Menu\Notifications\ThsNudge;
use App\Services\Menu\Notifications\ThsReplyNudge;
use App\Services\Menu\NudgeService;
use Illuminate\Support\ServiceProvider;


class NudgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        NudgeService::register(new AdNudge());
        NudgeService::register(new SharedEventsNudge());
        NudgeService::register(new SuggestionNudge());
        NudgeService::register(new PostNudge());
        NudgeService::register(new PhotoNudge());
        NudgeService::register(new ReportNudge());
        NudgeService::register(new FeedNudge());
        NudgeService::register(new TaskNudge('activity'));
        NudgeService::register(new TaskNudge('overdue'));
        NudgeService::register(new TaskNudge('approval'));
        NudgeService::register(new ProjectNudge('invite'));
        NudgeService::register(new ProjectNudge('step'));
        NudgeService::register(new ContactNudge());
        NudgeService::register(new ThsNudge());
        NudgeService::register(new ThsReplyNudge());
        NudgeService::register(new DmsNudge());
        NudgeService::register(new ChannelNudge());
        NudgeService::register(new ReminderOverdueNudge());
    }
}
