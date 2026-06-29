<?php

namespace App\Services\Menu\Notifications;

use App\Models\Photo;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class PhotoNudge implements MenuNudge
{
    public function getKey(): string
    {
        return 'gallery-controller:nudge';
    }

    public function triggers(): array
    {
        return [
            ['class' => Photo::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }

    public function show($subject, User $user): bool
    {
        return true;
    }

    public function for($subject)
    {
        $deps = $subject->all_departments;

        if (empty($deps)) {
            return User::active()->get();
        }

        return User::active()
            ->whereHas('profile', fn($q) => $q->whereIn('department_id', array_merge(['MA'], $deps)))
            ->get();
    }

    public function title($subject, User $user): string
    {
        return 'تصویر جدید در گالری: ' . $subject->title;
    }

    public function body($subject, User $user): string
    {
        return 'تصویر جدیدی در گالری آپلود شده است.';
    }

    public function refresh(): bool
    {
        return true;
    }
}
