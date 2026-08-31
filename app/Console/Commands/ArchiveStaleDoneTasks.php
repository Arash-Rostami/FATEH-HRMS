<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\Menu\StateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveStaleDoneTasks extends Command
{
    protected $signature = 'tasks:archive-stale-done';

    protected $description = 'Archive approved done tasks older than each project’s auto_archive_days (default 45), grouped by threshold for bulk update.';

    public function handle(): int
    {
        $touched = 0;

        Task::query()
            ->where('status', 'done')
            ->whereNotNull('approved_at')
            ->whereNull('archived_at')
            ->with('project:id,settings')
            ->select('id', 'project_id', 'approved_at')
            ->chunkById(200, function ($tasks) use (&$touched) {
                $groups = [];
                foreach ($tasks as $task) {
                    $days = $task->project?->setting('auto_archive_days') ?? 45;
                    if ($task->approved_at <= now()->subDays($days)) {
                        $groups[$days][] = $task->id;
                    }
                }
                foreach ($groups as $ids) {
                    DB::table('tasks')->whereIn('id', $ids)->update(['archived_at' => now()]);
                    $touched += count($ids);
                }
            });

        if ($touched > 0) {
            StateService::flush();
        }

        return self::SUCCESS;
    }
}