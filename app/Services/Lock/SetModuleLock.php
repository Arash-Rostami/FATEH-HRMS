<?php

namespace App\Services\Lock;

use App\Models\Lock;
use App\Models\User;
use App\Support\LockPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SetModuleLock
{
    private const MIN_CODE_BYTES = 8;

    public function handle(User $actor, string $module, string $code, ?string $slot = null): Lock
    {
        return $this->handleMany($actor, [$module], $code, $slot)->first();
    }

    public function handleMany(User $actor, array $modules, string $code, ?string $slot = null): Collection
    {
        $slot = Lock::slotFor($actor, $slot);
        $modules = array_values(array_unique($modules));

        abort_unless($slot !== null && $modules !== [] && array_diff($modules, array_keys(LockPolicy::lockableModules())) === [], 404);

        $hash = $this->hash($code);

        $locks = DB::transaction(fn (): Collection => collect($modules)->map(fn (string $module): Lock => Lock::updateOrCreate(
            ['lockable_type' => $module, 'lockable_id' => 0, 'slot' => $slot],
            ['secret_hash' => $hash],
        )));

        Log::info('module lock set', ['user_id' => $actor->id, 'modules' => $modules, 'slot' => $slot]);

        return $locks;
    }

    public function handleRecord(User $actor, string $module, int $recordId, string $code): Lock
    {
        abort_unless(Lock::ownsRecord($actor, $module, $recordId), 404);

        $lock = Lock::updateOrCreate(
            ['lockable_type' => $module, 'lockable_id' => $recordId, 'slot' => Lock::SLOT_OWNER],
            ['secret_hash' => $this->hash($code)],
        );

        Log::info('record lock set', ['user_id' => $actor->id, 'module' => $module, 'record' => $recordId]);

        return $lock;
    }

    private function hash(string $code): string
    {
        if (strlen($code) < self::MIN_CODE_BYTES) {
            throw ValidationException::withMessages(['code' => __('resources/lock/strings.manage.code_too_short')]);
        }

        if (strlen($code) > Lock::MAX_CODE_BYTES) {
            throw ValidationException::withMessages(['code' => __('resources/lock/strings.manage.code_too_long')]);
        }

        return Hash::make($code);
    }
}
