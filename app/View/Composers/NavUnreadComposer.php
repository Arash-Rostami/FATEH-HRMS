<?php

namespace App\View\Composers;

use Illuminate\View\View;

class NavUnreadComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with('navHasUnread', $user ? $user->unreadNotifications()->exists() : false);
    }
}