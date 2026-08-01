<?php

namespace App\Services\Menu\Notifications;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class SharedEventsNudge implements MenuNudge
{
    private bool $hasShares = false;

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function body($subject, User $user): string
    {
        return $user->id === $subject->user_id
            ? 'این رویداد توسط شما با همکاران به اشتراک گذاشته شده است.'
            : 'این رویداد توسط یکی از همکاران با شما به اشتراک گذاشته شده است.';
    }

    public function for($subject)
    {
        $shareUserIds = $subject->shares()->pluck('user_id')->all();
        $this->hasShares = !empty($shareUserIds);

        return User::active()->whereIn('id', collect([$subject->user_id])
            ->merge($shareUserIds)
            ->unique()
            ->filter()
            ->all())->get();
    }

    public function getKey(): string
    {
        return 'shared-events:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        if ($subject->date < now()) {
            return false;
        }

        if ($user->id !== $subject->user_id) {
            return true;
        }

        return $this->hasShares;
    }

    public function title($subject, User $user): string
    {
        return $user->id === $subject->user_id
            ? 'رویداد شما به اشتراک گذاشته شد: ' . $subject->title
            : 'رویداد مشترک: ' . $subject->title;
    }

    public function triggers(): array
    {
        return [
            ['class' => EventShare::class, 'on' => ['created', 'deleted'], 'subject' => fn(EventShare $share) => $share->event],
            ['class' => Event::class, 'on' => ['updated', 'deleted'], 'subject' => null],
        ];
    }

    public function url($subject): ?string
    {
        return route('dashboard', ['tab' => 'calendar', 'open' => $subject->getKey()]);
    }
}
