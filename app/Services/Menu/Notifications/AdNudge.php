<?php

namespace App\Services\Menu\Notifications;

use App\Models\Ad;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class AdNudge implements MenuNudge
{
    public function getKey(): string
    {
        return 'ads-controller:nudge';
    }

    public function triggers(): array
    {
        return [
            ['class' => Ad::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }

    public function show($subject, User $user): bool
    {
        return $subject->active;
    }

    public function for($subject)
    {
        return User::active()->get();
    }

    public function title($subject, User $user): string
    {
        return 'آگهی فعال: ' . ($subject->position ?: '#' . $subject->id);
    }

    public function body($subject, User $user): string
    {
        return 'این آگهی فعال است و نیاز به بررسی دارد.';
    }

    public function refresh(): bool
    {
        return true;
    }
}