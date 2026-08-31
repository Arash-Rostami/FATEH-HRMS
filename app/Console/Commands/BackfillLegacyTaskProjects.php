<?php

namespace App\Console\Commands;

use App\Services\Menu\StateService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillLegacyTaskProjects extends Command
{
    protected $signature = 'taskboard:backfill-legacy-projects {--dry-run : Preview affected legacy project names/task counts without writing}';

    protected $description = 'One-time, idempotent, per-project-transactioned backfill that resolves each distinct legacy task_details.project string into a Project row, backfills tasks.project_id, stamps already-done tasks as approved, seeds member_ids from task creators/assignees, and silently provisions a linked Channel (entered_at/joined_at pre-stamped — zero toasts/nudges). Re-runnable and resumable: the working set is always tasks WHERE project_id IS NULL AND task_details.project IS NOT NULL, so already-linked tasks are naturally excluded.';

    public function handle(): int
    {
        $legacyNames = DB::table('tasks')
            ->join('task_details', 'task_details.task_id', '=', 'tasks.id')
            ->whereNull('tasks.project_id')
            ->whereNotNull('task_details.project')
            ->whereRaw("TRIM(task_details.project) != ''")
            ->selectRaw('DISTINCT TRIM(task_details.project) as name')
            ->pluck('name');

        if ($legacyNames->isEmpty()) {
            $this->info('No unlinked legacy projects found — nothing to backfill.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[dry-run] ' : '') . "Found {$legacyNames->count()} distinct legacy project name(s) to process.");

        $linked = 0;
        $failed = 0;
        foreach ($legacyNames as $legacyName) {
            try {
                $taskCount = $dryRun
                    ? DB::table('tasks')
                        ->join('task_details', 'task_details.task_id', '=', 'tasks.id')
                        ->whereNull('tasks.project_id')
                        ->whereRaw('TRIM(task_details.project) = ?', [$legacyName])
                        ->count()
                    : DB::transaction(fn() => $this->backfillOne($legacyName));
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  failed \"{$legacyName}\": {$e->getMessage()}");
                continue;
            }

            $this->line(($dryRun ? '  [would link] ' : '  linked ') . "\"{$legacyName}\" — {$taskCount} task(s)");
            $linked += $taskCount;
        }

        $this->info(($dryRun ? '[dry-run] would link ' : 'Linked ') . "{$linked} task(s) across {$legacyNames->count()} project(s)." . ($failed > 0 ? " {$failed} legacy project(s) failed — see errors above." : ''));

        if (!$dryRun && $linked > 0) {
            StateService::flush();
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function backfillOne(string $legacyName): int
    {
        $taskRows = DB::table('tasks')
            ->join('task_details', 'task_details.task_id', '=', 'tasks.id')
            ->whereNull('tasks.project_id')
            ->whereRaw('TRIM(task_details.project) = ?', [$legacyName])
            ->select('tasks.id as task_id', 'tasks.user_id as creator_id', 'tasks.assigned_to', 'tasks.created_at', 'tasks.status')
            ->lockForUpdate()
            ->get();

        if ($taskRows->isEmpty()) {
            return 0;
        }

        $slug = $this->deterministicSlug($legacyName);
        $now = now();

        $existingProject = DB::table('projects')->where('slug', $slug)->first(['id', 'owner_id']);
        $projectId = $existingProject?->id;
        $ownerId = $existingProject?->owner_id;

        if (!$projectId) {
            $memberIds = $this->resolveExistingMemberIds($taskRows);
            $ownerId = $this->resolveOwner($taskRows, $memberIds);
            $created = false;

            try {
                $projectId = DB::table('projects')->insertGetId([
                    'name' => $legacyName,
                    'slug' => $slug,
                    'owner_id' => $ownerId,
                    'member_ids' => json_encode($memberIds->values()->all()),
                    'departments' => null,
                    'channel_id' => null,
                    'settings' => null,
                    'archived_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $created = true;
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                $existingProject = DB::table('projects')->where('slug', $slug)->first(['id', 'owner_id']);
                $projectId = $existingProject?->id;
                $ownerId = $existingProject?->owner_id;
                if (!$projectId) {
                    throw $e;
                }
            }

            if ($created) {
                $this->provisionChannelSilently($projectId, $legacyName, $slug, $ownerId, $memberIds, $now);
            }
        }

        DB::table('tasks')
            ->whereIn('id', $taskRows->pluck('task_id'))
            ->update(['project_id' => $projectId, 'updated_at' => $now]);

        $doneIds = $taskRows->where('status', 'done')->pluck('task_id')->all();

        if ($doneIds !== []) {
            DB::table('tasks')
                ->whereIn('id', $doneIds)
                ->whereNull('approved_at')
                ->update(['approved_at' => $now, 'approved_by' => $ownerId, 'updated_at' => $now]);
        }

        return $taskRows->count();
    }

    private function deterministicSlug(string $legacyName): string
    {
        return mb_substr($this->transliterate($legacyName), 0, 150) . '-' . substr(md5($legacyName), 0, 8);
    }

    private function transliterate(string $name): string
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim($name)));
        $base = trim(preg_replace('/-{2,}/u', '-', (string) $base), '-');

        return $base !== '' ? $base : 'project';
    }

    private function resolveExistingMemberIds(Collection $taskRows): Collection
    {
        $participantIds = $taskRows->flatMap(fn($row) => [$row->creator_id, $row->assigned_to])
            ->filter()
            ->unique()
            ->values();

        if ($participantIds->isEmpty()) {
            return collect();
        }

        return DB::table('users')->whereIn('id', $participantIds)->pluck('id');
    }

    private function resolveOwner(Collection $taskRows, Collection $existingMemberIds): ?int
    {
        $counts = $taskRows->groupBy('creator_id')->map->count();
        if ($counts->isEmpty()) {
            return null;
        }

        $maxCount = $counts->max();
        $tied = $counts->filter(fn($count) => $count === $maxCount)->keys();

        $ownerId = $tied->count() === 1
            ? $tied->first()
            : $taskRows->whereIn('creator_id', $tied->all())->sortBy('created_at')->first()->creator_id;

        return $existingMemberIds->contains($ownerId) ? $ownerId : null;
    }

    private function provisionChannelSilently(
        int $projectId,
        string $legacyName,
        string $projectSlug,
        ?int $ownerId,
        Collection $memberIds,
        Carbon $now
    ): void {
        $channelSlug = mb_substr($this->transliterate($legacyName), 0, 100) . '-' . substr(md5($projectSlug . '-channel'), 0, 8);

        $channelId = DB::table('channels')->insertGetId([
            'name' => mb_substr($legacyName, 0, 100),
            'slug' => $channelSlug,
            'description' => null,
            'type' => 'private',
            'owner_id' => $ownerId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $memberRows = $memberIds->map(fn($userId) => [
            'user_id' => $userId,
            'channel_id' => $channelId,
            'joined_at' => $now,
            'entered_at' => $now,
            'last_read_message_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($memberRows)) {
            DB::table('channel_members')->insert($memberRows);
        }

        DB::table('projects')->where('id', $projectId)->update(['channel_id' => $channelId, 'updated_at' => $now]);
    }
}
