<?php

namespace App\Services\Menu;

use App\Models\User;
use App\Services\Menu\Indicators\ActiveAds;
use App\Services\Menu\Indicators\EnergyTestBadge;
use App\Services\Menu\Indicators\PendingSuggestions;
use App\Services\Menu\Indicators\SharedEvents;
use App\Services\Menu\Indicators\SpecialDays;
use App\Services\Menu\Indicators\TasksTodo;
use App\Services\Menu\Indicators\ThsBadge;
use App\Services\Menu\Indicators\TodayFeeds;
use App\Services\Menu\Indicators\TodayPosts;

use App\Services\Menu\Indicators\DmsBadge;
use App\Services\Menu\Indicators\UnreadMessages;
use Illuminate\Support\Facades\Cache;

class StateService
{
    private const VERSION_KEY = 'menu_state:version';
    private const TTL_HOURS = 2;

    private array $indicators = [
        ActiveAds::class,
        PendingSuggestions::class,
        SharedEvents::class,
        TodayPosts::class,
        TodayFeeds::class,
        SpecialDays::class,
        TasksTodo::class,
        UnreadMessages::class,
        EnergyTestBadge::class,
        ThsBadge::class,
        DmsBadge::class,

    ];

    public function __construct(
        private readonly BadgeSyncService $syncService
    )
    {
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, now()->getPreciseTimestamp());
    }

    public function get(): array
    {
        $user = auth()->user();
        $version = self::version();
        $cacheKey = "menu_state:v{$version}:" . ($user ? "u{$user->id}" : 'guest');

        return Cache::remember($cacheKey, now()->addHours(self::TTL_HOURS), function () use ($user, $version) {
            $resolved = [];
            foreach ($this->indicators as $indicatorClass) {
                $indicator = app($indicatorClass);
                $resolved[$indicator->getKey()] = $indicator->isActive();
            }

            if ($user) {
                $this->syncIndicators($user, $resolved);
            }

            return $resolved;
        });
    }

    private function syncIndicators(User $user, array $state): void
    {
        foreach ($this->indicators as $indicatorClass) {
            $indicator = app($indicatorClass);

            try {
                $this->syncService->sync($user, $indicator, $state[$indicator->getKey()] ?? false);
            } catch (\Throwable) {
            }
        }
    }

    private static function version(): int
    {
        return Cache::rememberForever(self::VERSION_KEY, fn(): int => now()->getPreciseTimestamp());
    }
}
