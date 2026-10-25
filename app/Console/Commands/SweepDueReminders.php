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

    protected $description = 'Hourly safety net: reconcile the due bell nudge and due-today edge toast of every reminder whose due time has passed, send the queued email for due-today reminders on the email channel that have not been notified yet, and (re)queue the delayed due-time reconcile of every reminder that falls due within the next 24 hours.';

    public function handle(): int
    {
        $overdueKey = (new ReminderOverdueNudge())->getKey();

        Reminder::query()
            ->active()
            ->where('due_at', '<=', now())
            ->select('id')
            ->chunkById(200, function ($reminders) use ($overdueKey) {
                foreach ($reminders as $reminder) {
                    ReconcileNudge::dispatch($overdueKey, Reminder::class, $reminder->id);
                }
            });

        $dueTodayKey = (new ReminderDueTodayEdge())->getKey();

        Reminder::query()
            ->active()
            ->where('due_at', '>', now()->subDay())
            ->where('due_at', '<=', now())
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

        Reminder::query()
            ->whereNull('completed_at')
            ->where(fn ($q) => $q
                ->whereBetween('due_at', [now(), now()->addDay()])
                ->orWhereBetween('snoozed_until', [now(), now()->addDay()]))
            ->select(['id', 'due_at', 'snoozed_until', 'completed_at'])
            ->chunkById(200, function ($reminders) {
                foreach ($reminders as $reminder) {
                    $reminder->queueDueReconcile();
                }
            });

        return self::SUCCESS;
    }
}
