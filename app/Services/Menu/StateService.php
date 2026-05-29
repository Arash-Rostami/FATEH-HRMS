<?php

namespace App\Services\Menu;

use App\Services\Menu\Indicators\ActiveAds;
use App\Services\Menu\Indicators\PendingSuggestions;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Facades\Cache;

class StateService
{
    private const VERSION_KEY = 'menu_state:version';
    private const TTL_HOURS = 2;

    private array $indicators = [
        ActiveAds::class,
        PendingSuggestions::class,
    ];

    public function __construct(
        private readonly BadgeSyncService $syncService
    )
    {
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, now()->getPreciseTimestamp());
        app(self::class)->purgeMenuNotifications();
    }

    public function get(): array
    {
        $user = auth()->user();
        $version = self::version();
        $cacheKey = "menu_state:v{$version}:" . ($user ? "u{$user->id}" : 'guest');

        $state = Cache::remember($cacheKey, now()->addHours(self::TTL_HOURS), function () {
            $resolved = [];
            foreach ($this->indicators as $indicatorClass) {
                $indicator = app($indicatorClass);
                $resolved[$indicator->getKey()] = $indicator->isActive();
            }
            return $resolved;
        });

        if ($user) {
            foreach ($this->indicators as $indicatorClass) {
                $indicator = app($indicatorClass);
                $this->syncService->sync($user, $indicator, $state[$indicator->getKey()] ?? false, $version);
            }
        }

        return $state;
    }

    private function purgeMenuNotifications(): void
    {
        $keys = array_map(fn($class) => app($class)->getKey(), $this->indicators);

        FilamentDatabaseNotification::where('type', FilamentDatabaseNotification::class)
            ->where(function ($q) use ($keys) {
                foreach ($keys as $key) {
                    $q->orWhere('data->menu_key', $key);
                }
            })
            ->delete();
    }

    private static function version(): int
    {
        return Cache::rememberForever(self::VERSION_KEY, fn(): int => now()->getPreciseTimestamp());
    }
}
