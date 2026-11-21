<?php

namespace App\Services\Menu\Notifications;

use App\Models\Media;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class MediaNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'رسانهٔ جدیدی منتشر شده است؛ لطفاً بررسی نمایید.';
    }

    public function for($subject)
    {
        $subject->loadMissing('department');

        return $subject->audienceUsers();
    }

    public function getKey(): string
    {
        return Media::NUDGE_KEY;
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return (bool) $subject->active
            && ($subject->expires_at === null || $subject->expires_at->isFuture())
            && ($subject->report_date === null || !$subject->report_date->isFuture());
    }

    public function title($subject, User $user): string
    {
        return 'رسانهٔ جدید از ' . ($subject->department?->displayLabel() ?? 'سازمان') . ': ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => Media::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => fn (Media $media) => $media->parent_id ? null : $media],
        ];
    }

    public function url($subject): ?string
    {
        return route('media', ['open' => $subject->getKey()]);
    }
}
