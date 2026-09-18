<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Command;

class PruneStaleReminders extends Command
{
    protected $signature = 'reminders:prune-completed {--days=90 : Prune completed reminders older than N days}';

    protected $description = 'Prune completed reminder rows (completed_at set) older than N days. The reminder is permanent history until pruned by this command; uncompleted/active reminders are never touched.';

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
            $deleted = Reminder::query()
                ->whereNotNull('completed_at')
                ->where('completed_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $count += $deleted;
        } while ($deleted > 0);

        $this->info("Pruned {$count} completed reminder rows older than {$days} days (cutoff {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
