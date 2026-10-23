<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Models\Task;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\Menu\StateService;
use App\Services\Menu\Toasts\TaskDueSoonToast;
use App\Services\ProjectTask\TaskMetrics;
use Illuminate\Console\Command;

class SweepOverdueTasks extends Command
{
    protected $signature = 'tasks:sweep-overdue';

    protected $description = 'Sweep tasks whose deadline has passed with no accompanying save (so no Eloquent event fired) and reconcile the overdue bell nudge (doer plus one-time accountable escalation) for each, tasks that silently crossed in or out of the due-soon window and reconcile the due-soon edge toast, and done tasks waiting for approval so the approval nudge escalates — the only time-driven triggers in the nudge/edge systems, everything else is event-driven.';

    public function handle(): int
    {
        $overdueKey = (new TaskNudge('overdue'))->getKey();

        TaskMetrics::overdue(Task::query())
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
            ->whereBetween('deadline', [now()->subDay(), now()->addHours(24)])
            ->select('id')
            ->chunkById(200, function ($tasks) use ($dueSoonKey) {
                foreach ($tasks as $task) {
                    ReconcileEdge::dispatch($dueSoonKey, Task::class, $task->id);
                }
            });

        $approvalKey = (new TaskNudge('approval'))->getKey();

        Task::query()
            ->whereNull('archived_at')
            ->where('status', 'done')
            ->whereNull('approved_at')
            ->where('completed_at', '<', now()->subDay())
            ->select('id')
            ->chunkById(200, function ($tasks) use ($approvalKey) {
                foreach ($tasks as $task) {
                    ReconcileNudge::dispatch($approvalKey, Task::class, $task->id);
                }
            });

        StateService::flush();

        return self::SUCCESS;
    }
}
