<?php

namespace App\View\Composers;

use Illuminate\View\View;

class NavUnreadComposer
{
    public function compose(View $view): void
    {
        $view->with('navHasUnread', auth()->check() && auth()->user()->unreadNotifications()->exists());
    }
}