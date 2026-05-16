<?php

namespace App\Livewire\Menu;

use Livewire\Component;
use Livewire\Attributes\On;

class BadgeManager extends Component
{
    #[On('clearBadge')]
    public function clearBadge(string $notificationId, string $menuKey)
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        // 1. Delete the notification from the DB
        $user->notifications()->where('id', $notificationId)->delete();

        // 2. Add to user's cleared_badges preferences
        $extra = $user->extra ?? [];

        if (!isset($extra['cleared_badges'])) {
            $extra['cleared_badges'] = [];
        }

        if (!in_array($menuKey, $extra['cleared_badges'])) {
            $extra['cleared_badges'][] = $menuKey;
            $user->extra = $extra;
            $user->save();
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div></div>
        HTML;
    }
}
