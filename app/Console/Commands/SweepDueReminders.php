<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Reminder;
use App\Notifications\ReminderDueNotification;
use App\Services\Menu\Notifications\ReminderOverdueNudge;
use App\Services\Menu\Toasts\ReminderDueTodayEdge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SweepDueReminders extends Command
{
    protected $signature = 'reminders:sweep-due';

    protected $description = 'Sweep reminders that are due today or overdue with no accompanying save (so no Eloquent event fired) and reconcile the due bell nudge and due-today edge toast for each, plus send the queued email notification for due-today reminders on the email channel that have not been notified yet.';

    public function handle(): int
    {
        $overdueKey = (new ReminderOverdueNudge())->getKey();

        Reminder::query()
            ->active()
            ->where('due_at', '<=', now()->endOfDay())
            ->select('id')
            ->chunkById(200, function ($reminders) use ($overdueKey) {
                foreach ($reminders as $reminder) {
                    ReconcileNudge::dispatch($overdueKey, Reminder::class, $reminder->id);
                }
            });

        $dueTodayKey = (new ReminderDueTodayEdge())->getKey();

        Reminder::query()
            ->active()
            ->whereDate('due_at', now()->toDateString())
            ->with('user')
            ->chunkById(200, function ($reminders) use ($dueTodayKey) {
                foreach ($reminders as $reminder) {
                    ReconcileEdge::dispatch($dueTodayKey, Reminder::class, $reminder->id);

                    if (!in_array('email', $reminder->channels, true) || $reminder->notified_at !== null || $reminder->hostTrashed()) {
                        continue;
                    }

                    $affected = Reminder::whereKey($reminder->id)->whereNull('notified_at')->update(['notified_at' => now()]);

                    if ($affected === 1) {
                        Notification::send($reminder->user, new ReminderDueNotification($reminder));
                    }
                }
            });

        return self::SUCCESS;
    }
}
