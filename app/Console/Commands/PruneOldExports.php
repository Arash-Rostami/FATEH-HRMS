<?php

namespace App\Console\Commands;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneOldExports extends Command
{
    protected $signature = 'exports:prune-old {--days=30 : Delete Filament export rows and their files older than N days}';

    protected $description = 'Deletes Filament export rows older than N days together with their filament_exports/{id} directory on the export disk. Files go first so a failed file delete keeps the row for the next run.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;
        $failed = 0;

        Export::query()->where('created_at', '<', $cutoff)->lazyById(200)->each(function (Export $export) use (&$deleted, &$failed) {
            try {
                $export->deleteFileDirectory();
                $export->delete();
                $deleted++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('exports:prune-old: failed to delete export', ['export_id' => $export->id, 'error' => $e->getMessage()]);
            }
        });

        $this->info("Deleted {$deleted} exports older than {$days} days (cutoff {$cutoff->toDateTimeString()}); {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
