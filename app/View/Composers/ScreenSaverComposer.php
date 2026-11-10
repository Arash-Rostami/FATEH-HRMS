<?php

namespace App\View\Composers;

use Illuminate\View\View;

class ScreenSaverComposer
{
    private const DEFAULT_TIMEOUT = 60;

    public function compose(View $view): void
    {
        $user = auth()->user();
        $preferences = $user?->extra['preferences'] ?? [];

        $view->with([
            'isScreenSaverEnabled' => (bool) ($user && ($preferences['screen_saver'] ?? true)),
            'timeout' => isset($preferences['screen_saver_timeout']) ? (int) $preferences['screen_saver_timeout'] : self::DEFAULT_TIMEOUT,
        ]);
    }
}
