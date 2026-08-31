<?php

namespace App\Console\Commands;

use App\Models\Edge;
use Illuminate\Console\Command;

class PruneStaleEdges extends Command
{
    protected $signature = 'edges:prune-stale {--days=30 : Prune permanently-dismissed edge rows older than N days}';

    protected $description = 'Prune dead edge rows (dismissed_at set) older than N days. Active and snoozed edges are untouched.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $count = 0;

        do {
            $deleted = Edge::query()
                ->whereNotNull('dismissed_at')
                ->where('dismissed_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $count += $deleted;
        } while ($deleted > 0);

        $this->info("Pruned {$count} stale edge rows older than {$days} days (cutoff {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}