<?php

namespace App\Services\Lock;

use App\Models\Lock;
use App\Models\User;
use App\Support\LockPolicy;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UnlockModule
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;

    public function handle(User $actor, string $module, string $code, int $recordId = 0): bool
    {
        abort_unless($recordId > 0 ? in_array($module, Lock::RECORD_MODULES, true) : array_key_exists($module, LockPolicy::lockableModules()), 404);

        $locks = Lock::query()->where('lockable_type', $module)->where('lockable_id', $recordId)->get();

        if ($locks->isEmpty()) {
            return true;
        }

        $key = "lock-unlock:{$actor->id}";

        abort_if(RateLimiter::hit($key, self::DECAY_SECONDS) > self::MAX_ATTEMPTS, 429);

        $match = $locks->first(fn (Lock $lock): bool => Hash::check($code, $lock->secret_hash));

        if ($match === null) {
            Log::warning('module unlock failed', ['user_id' => $actor->id, 'module' => $module, 'record' => $recordId, 'ip' => request()->ip()]);

            return false;
        }

        RateLimiter::decrement($key, self::DECAY_SECONDS);
        LockPolicy::remember($module, $match);
        Log::info('module unlocked', ['user_id' => $actor->id, 'module' => $module, 'slot' => $match->slot]);

        return true;
    }
}
