<?php

namespace App\Livewire\Dashboard\Channel\Actions;

use App\Models\Lock;
use App\Services\Lock\ClearModuleLock;
use App\Services\Lock\SetModuleLock;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SetChannelKeyAction
{
    public const MODULE = 'channel';

    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 600;

    public function execute(int $channelId, int $userId, ?string $code): bool
    {
        $user = auth()->user();

        abort_unless($user !== null && $user->id === $userId && Lock::ownsRecord($user, self::MODULE, $channelId), 404);

        $limiterKey = "channel-key:{$userId}";

        if (RateLimiter::hit($limiterKey, self::DECAY_SECONDS) > self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages(['code' => 'تلاش‌های پیاپی زیاد بود؛ کمی بعد دوباره تلاش کنید.']);
        }

        if (blank($code)) {
            app(ClearModuleLock::class)->handleRecord($user, self::MODULE, $channelId);

            return false;
        }

        app(SetModuleLock::class)->handleRecord($user, self::MODULE, $channelId, $code);

        return true;
    }
}
