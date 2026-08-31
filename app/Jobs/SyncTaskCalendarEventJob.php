<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\ProjectTask\EventSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTaskCalendarEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public int $taskId) { }

    public function handle(EventSyncService $service): void
    {
        $task = Task::find($this->taskId);

        if (!$task) return;

        $service->sync($task);
    }
}
