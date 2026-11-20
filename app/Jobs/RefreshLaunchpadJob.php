<?php

namespace App\Jobs;

use App\Livewire\Dashboard\Launchpad\Actions\RefreshLaunchpadAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshLaunchpadJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(public int $id)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->id;
    }

    public function handle(RefreshLaunchpadAction $action): void
    {
        if (tenantOff('launchpad')) {
            return;
        }

        try {
            $action->execute($this->id);
        } catch (Throwable) {
            Log::warning('launchpad refresh failed', ['item' => $this->id, 'code' => 'error']);
        }
    }
}
