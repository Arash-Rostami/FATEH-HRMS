<?php

namespace App\Services\Menu;

use App\Services\Menu\Indicators\ActiveAds;
use App\Services\Menu\Indicators\EnergyTestBadge;
use App\Services\Menu\Indicators\PendingSuggestions;
use App\Services\Menu\Indicators\SharedEvents;
use App\Services\Menu\Indicators\SpecialDays;
use App\Services\Menu\Indicators\TasksImminent;
use App\Services\Menu\Indicators\TasksPendingApproval;
use App\Services\Menu\Indicators\TasksTodo;
use App\Services\Menu\Indicators\ThsBadge;
use App\Services\Menu\Indicators\UnreadFeeds;
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
        UnreadFeeds::class,
        SpecialDays::class,
        TasksTodo::class,
        TasksImminent::class,
        TasksPendingApproval::class,
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

    public static function markViewed(string $tab): void
    {
        $user = auth()->user();
        if ($user === null || self::viewedToday($tab)) {
            return;
        }

        Cache::put(self::viewedKey($tab, $user->id), now()->toDateString(), now()->endOfDay());
        self::flush();
    }

    public static function viewedToday(string $tab): bool
    {
        $user = auth()->user();

        return $user !== null && Cache::get(self::viewedKey($tab, $user->id)) === now()->toDateString();
    }

    private static function viewedKey(string $tab, int $userId): string
    {
        return "tab_viewed:{$tab}:{$userId}";
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
