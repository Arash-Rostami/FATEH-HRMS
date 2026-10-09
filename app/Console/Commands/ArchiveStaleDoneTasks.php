<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\StateService;
use Illuminate\Console\Command;

class ArchiveStaleDoneTasks extends Command
{
    protected $signature = 'tasks:archive-stale-done';

    protected $description = 'Archive approved done tasks older than each project’s auto_archive_days (default 45).';

    public function handle(): int
    {
        $touched = 0;

        Task::query()
            ->where('status', 'done')
            ->whereNotNull('approved_at')
            ->whereNull('archived_at')
            ->with('project:id,settings')
            ->select('id', 'project_id', 'approved_at', 'updated_at')
            ->chunkById(200, function ($tasks) use (&$touched) {
                foreach ($tasks as $task) {
                    $days = $task->project?->charter()->autoArchiveDays() ?? 45;
                    $anchor = $task->updated_at ? $task->approved_at->max($task->updated_at) : $task->approved_at;
                    if ($anchor <= now()->subDays($days)) {
                        $task->update(['archived_at' => now()]);
                        $touched++;
                    }
                }
            });

        if ($touched > 0) {
            ModelCacheVersion::bump(Task::class);
            StateService::flush();
        }

        return self::SUCCESS;
    }
}
