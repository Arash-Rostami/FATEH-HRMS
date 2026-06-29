<?php

namespace App\Services\Menu\Notifications;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use App\Models\Feed;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class FeedNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'یک خبر جدید منتشر شده است؛ برای مشاهده به بخش اخبار مراجعه کنید.';
    }

    public function for($subject)
    {
        return User::active()->get();
    }

    public function getKey(): string
    {
        return 'feeds:nudge';
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
        $label = FeedCategory::tryFrom($subject->category)?->getLabel();

        return 'خبر جدید' . ($label ? ': ' . $label : '');
    }

    public function triggers(): array
    {
        return [
            ['class' => Feed::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ];
    }
}