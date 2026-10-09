<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\TaskDetail;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeTaskCollaborators extends Command
{
    protected $signature = 'tasks:normalize-collaborators';

    protected $description = 'Rewrite task_details.collaborators and projects.member_ids so every id is stored as a number, never text.';

    public function handle(): int
    {
        $fixed = 0;

        DB::table('task_details')
            ->whereNotNull('collaborators')
            ->where('collaborators', 'like', '%"%')
            ->select('id', 'collaborators')
            ->orderBy('id')
            ->each(function ($row) use (&$fixed) {
                $ids = array_values(array_unique(array_map('intval', json_decode($row->collaborators, true) ?: [])));

                DB::table('task_details')->where('id', $row->id)->update(['collaborators' => json_encode($ids)]);
                $fixed++;
            });

        if ($fixed) {
            ModelCacheVersion::bump(TaskDetail::class);
        }

        $fixedProjects = 0;

        DB::table('projects')
            ->whereNotNull('member_ids')
            ->where('member_ids', 'like', '%"%')
            ->select('id', 'member_ids')
            ->orderBy('id')
            ->each(function ($row) use (&$fixedProjects) {
                $ids = array_values(array_unique(array_map('intval', json_decode($row->member_ids, true) ?: [])));

                DB::table('projects')->where('id', $row->id)->update(['member_ids' => json_encode($ids)]);
                $fixedProjects++;
            });

        if ($fixedProjects) {
            ModelCacheVersion::bump(Project::class);
        }

        $fixed += $fixedProjects;

        $this->info("Normalized {$fixed} row(s).");

        return self::SUCCESS;
    }
}
