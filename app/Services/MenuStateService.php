<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Cache;

class MenuStateService
{
    private const VERSION_KEY = 'menu_state:version';
    private const TTL_HOURS = 2;

    public function get(): array
    {
        $user = auth()->user();
        $version = self::version();
        $cacheKey = "menu_state:v{$version}:" . ($user ? "u{$user->id}" : 'guest');

        return Cache::remember($cacheKey, now()->addHours(self::TTL_HOURS), function () {
            return [
                'ads-controller' => $this->forAds(),
                'suggestion-controller' => $this->forSuggestion(),
            ];
        });
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, now()->getPreciseTimestamp());
    }

    private static function version(): int
    {
        return Cache::rememberForever(self::VERSION_KEY, fn(): int => now()->getPreciseTimestamp());
    }

    private function forAds(): bool { return Ad::active()->exists(); }

    private function forSuggestion(): bool { return Suggestion::attentionRequired()->exists(); }
}
