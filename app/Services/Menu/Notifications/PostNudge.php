<?php

namespace App\Services\Menu\Notifications;

use App\Models\Post;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class PostNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'یک اطلاعیه جدید منتشر شده است؛ لطفاً آن را مطالعه کنید.';
    }

    public function for($subject)
    {
        return User::active()->get();
    }

    public function getKey(): string
    {
        return 'posts-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return true;
    }

    public function title($subject, User $user): string
    {
        return 'اطلاعیه جدید: ' . superClean($subject->title, 100);
    }

    public function triggers(): array
    {
        return [
            ['class' => Post::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }

    public function url($subject): ?string
    {
        return route('dashboard', ['tab' => 'post', 'open' => $subject->getKey()]);
    }
}
