<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Reminder;
use App\Services\Menu\Notifications\ReminderOverdueNudge;
use App\Services\Menu\Toasts\ReminderDueTodayEdge;
use Illuminate\Console\Command;

class SweepDueReminders extends Command
{
    protected $signature = 'reminders:sweep-due';

    protected $description = 'Hourly safety net: reconcile the due bell nudge and due-today edge toast of every reminder whose due time has passed, deliver the due email and SMS of reminders due within the last 24 hours through Reminder::deliverDue(), and (re)queue the delayed due-time reconcile of every reminder that falls due within the next 24 hours.';

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

                    $reminder->deliverDue();
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
