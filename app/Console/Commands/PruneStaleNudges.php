<?php

namespace App\Console\Commands;

use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneStaleNudges extends Command
{
    protected $signature = 'notifications:prune-stale {--days=30 : Prune nudge-namespace rows older than N days}';

    protected $description = 'Prune stale record-nudge rows (data->menu_key ending "nudge" — the nudge-namespace suffix family, current ":nudge" and legacy ":reply-nudge") older than N days, aligned to HasNudgeTracking::FRESHNESS_DAYS — by then they no longer feed any badge query and are only bell clutter. Bare-key badge rows (never ending "nudge") are untouched.';

    public function handle(): int
    {
        $days = (int)$this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $count = 0;

        do {
            $deleted = DatabaseNotification::query()
                ->where('type', FilamentDatabaseNotification::class)
                ->where('data->menu_key', 'like', '%nudge')
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $count += $deleted;
        } while ($deleted > 0);

        $this->info("Pruned {$count} stale nudge rows older than {$days} days (cutoff {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
