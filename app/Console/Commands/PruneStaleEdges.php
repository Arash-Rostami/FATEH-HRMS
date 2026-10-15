<?php

namespace App\Console\Commands;

use App\Jobs\ReconcileEdge;
use App\Models\Edge;
use App\Services\Menu\EdgeService;
use Illuminate\Console\Command;

class PruneStaleEdges extends Command
{
    protected $signature = 'edges:prune-stale {--days=30 : Re-evaluate permanently-dismissed edge rows older than N days}';

    protected $description = 'Re-evaluate subjects of forever-dismissed edge rows older than N days through the normal reconcile: rows no longer eligible are deleted, still-eligible ones stay dismissed so they can never resurface. Rows of unregistered rules are deleted. Active and snoozed edges are untouched.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $orphans = 0;

        if ($keys = EdgeService::keys()) {
            do {
                $deleted = Edge::query()
                    ->whereNotIn('edge_key', $keys)
                    ->limit(1000)
                    ->delete();
                $orphans += $deleted;
            } while ($deleted > 0);
        }

        $subjects = 0;

        $stale = Edge::query()
            ->whereNotNull('dismissed_at')
            ->where('dismissed_at', '<', $cutoff)
            ->select('edge_key', 'subject_type', 'subject_id')
            ->distinct()
            ->cursor();

        foreach ($stale as $row) {
            ReconcileEdge::dispatch($row->edge_key, $row->subject_type, $row->subject_id);
            $subjects++;
        }

        $this->info("Deleted {$orphans} orphan edge rows; re-evaluated {$subjects} subjects with dismissed rows older than {$days} days (cutoff {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}