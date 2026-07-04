<?php

namespace App\Services\Menu;

use App\Services\Menu\Indicators\ActiveAds;
use App\Services\Menu\Indicators\EnergyTestBadge;
use App\Services\Menu\Indicators\PendingSuggestions;
use App\Services\Menu\Indicators\SharedEvents;
use App\Services\Menu\Indicators\SpecialDays;
use App\Services\Menu\Indicators\TasksTodo;
use App\Services\Menu\Indicators\ThsBadge;
use App\Services\Menu\Indicators\TodayFeeds;
use App\Services\Menu\Indicators\UnreadPosts;

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
        UnreadPosts::class,
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

        return Cache::remember($cacheKey, now()->addHours(self::TTL_HOURS), function () use ($user) {
            $instances = array_map(static fn (string $class) => app($class), $this->indicators);

            $resolved = [];
            foreach ($instances as $indicator) {
                $resolved[$indicator->getKey()] = $indicator->isActive();
            }

            if ($user) {
                try {
                    $this->syncService->syncBatch($user, $instances, $resolved);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $resolved;
        });
    }

    private static function version(): int
    {
        return Cache::rememberForever(self::VERSION_KEY, fn(): int => now()->getPreciseTimestamp());
    }
}
