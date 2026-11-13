<?php

namespace App\Services\Lock;

use App\Models\Lock;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ClearModuleLock
{
    public function handle(User $actor, string $module, ?string $slot = null): void
    {
        $slot = Lock::slotFor($actor, $slot);

        abort_if($slot === null, 404);

        Lock::query()
            ->where('lockable_type', $module)
            ->where('lockable_id', 0)
            ->where('slot', $slot)
            ->first()
            ?->delete();

        Log::info('module lock cleared', ['user_id' => $actor->id, 'module' => $module, 'slot' => $slot]);
    }

    public function handleRecord(User $actor, string $module, int $recordId): bool
    {
        abort_unless($actor->isDeveloper() || Lock::ownsRecord($actor, $module, $recordId), 404);

        $lock = Lock::query()
            ->where('lockable_type', $module)
            ->where('lockable_id', $recordId)
            ->where('slot', Lock::SLOT_OWNER)
            ->first();

        $lock?->delete();

        Log::info('record lock cleared', ['user_id' => $actor->id, 'module' => $module, 'record' => $recordId]);

        return $lock !== null;
    }
}
