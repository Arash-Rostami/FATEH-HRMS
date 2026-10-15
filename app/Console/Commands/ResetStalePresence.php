<?php

namespace App\Console\Commands;

use App\Enums\PresenceStatus;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Console\Command;

class ResetStalePresence extends Command
{
    protected $signature = 'presence:reset-stale {--hours=12 : Reset mood statuses of users offline longer than N hours}';

    protected $description = 'Resets the mood statuses (busy, grumpy, angry) of users who have been offline longer than N hours back to the default presence and clears their focus timer. Planned absences (leave, mission) and workplace statuses (onsite, remote) are never touched.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        if ($hours < 1) {
            $this->error('The --hours option must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subHours($hours);

        $count = User::query()
            ->whereIn('presence', PresenceStatus::moodValues())
            ->where(fn ($query) => $query->whereNull('last_seen')->orWhere('last_seen', '<', $cutoff))
            ->toBase()
            ->update(['presence' => PresenceStatus::default()->value, 'focus_until' => null]);

        if ($count > 0) {
            ModelCacheVersion::bump(User::class);
        }

        $this->info("Reset {$count} stale mood presences (offline since before {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
