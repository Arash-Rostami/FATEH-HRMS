<?php

namespace App\Services\Menu\Notifications;

use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;

class SuggestionNudge implements MenuNudge
{
    public function getKey(): string
    {
        return 'suggestion-controller:nudge';
    }

    public function triggers(): array
    {
        return [
            ['class' => Suggestion::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
            ['class' => Review::class, 'on' => ['created', 'updated'], 'subject' => fn(Review $review) => $review->suggestion],
        ];
    }

    public function show($subject, User $user): bool
    {
        return Suggestion::requiresAttentionFor($subject, $user);
    }

    public function for($subject)
    {
        return User::active()
            ->whereHas('profile', fn($q) => $q->whereIn('department_id', array_merge(['MA'], $subject->departments ?? [])))
            ->get();
    }

    public function title($subject, User $user): string
    {
        return 'پیشنهاد: ' . $subject->title;
    }

    public function body($subject, User $user): string
    {
        return 'این پیشنهاد نیازمند تصمیم یا پیگیری است.';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function url($subject): ?string
    {
        return route('suggestion', ['open' => $subject->getKey()]);
    }
}