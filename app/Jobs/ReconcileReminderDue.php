<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\Menu\EdgeService;
use App\Services\Menu\NudgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class ReconcileReminderDue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public int $reminderId) {}

    public function handle(): void
    {
        DB::transaction(
            fn () => NudgeService::reconcile('reminders-controller:due-nudge', Reminder::class, $this->reminderId),
            3,
        );

        EdgeService::reconcile('reminders-controller:due-today-edge', Reminder::class, $this->reminderId);
    }
}
