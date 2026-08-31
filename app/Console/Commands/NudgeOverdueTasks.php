<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Task;
use App\Services\Menu\Notifications\TaskOverdueNudge;
use App\Services\Menu\Toasts\TaskDueSoonToast;
use Illuminate\Console\Command;

class NudgeOverdueTasks extends Command
{
    protected $signature = 'tasks:nudge-overdue';

    protected $description = 'Sweep tasks whose deadline has passed with no accompanying save (so no Eloquent event fired) and reconcile the overdue bell nudge for each, plus tasks that silently entered the due-soon (≤24h) window and reconcile the due-soon edge toast — the only time-driven triggers in the nudge/edge systems, everything else is event-driven.';

    public function handle(): int
    {
        $overdueKey = (new TaskOverdueNudge())->getKey();

        Task::query()
            ->whereNull('archived_at')
            ->whereNotIn('status', ['done', 'pending'])
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->select('id')
            ->chunkById(200, function ($tasks) use ($overdueKey) {
                foreach ($tasks as $task) {
                    ReconcileNudge::dispatch($overdueKey, Task::class, $task->id);
                }
            });

        $dueSoonKey = (new TaskDueSoonToast())->getKey();

        Task::query()
            ->whereNull('archived_at')
            ->whereNotIn('status', ['done', 'pending'])
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now(), now()->addHours(24)])
            ->select('id')
            ->chunkById(200, function ($tasks) use ($dueSoonKey) {
                foreach ($tasks as $task) {
                    ReconcileEdge::dispatch($dueSoonKey, Task::class, $task->id);
                }
            });

        return self::SUCCESS;
    }
}
