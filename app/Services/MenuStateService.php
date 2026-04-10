<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Cache;

class MenuStateService
{
    public function get(): array
    {
        $user = auth()->user();
        $cacheKey = $user ? "menu_state:{$user->id}" : 'menu_state:guest';

        return Cache::remember($cacheKey, now()->addHours(2), function () {
            return [
                'ads-controller' => $this->forAds(),
                'suggestion-controller' => $this->forSuggestion(),
            ];
        });
    }

    private function forAds(): bool { return Ad::active()->exists(); }

    private function forSuggestion(): bool { return Suggestion::attentionRequired()->exists(); }
}
