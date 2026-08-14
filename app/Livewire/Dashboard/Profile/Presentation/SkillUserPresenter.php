<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Enums\SkillTier;
use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Support\Collection;

class SkillUserPresenter
{
    private array $userCache = [];

    public function contextLabel(SkillUser $skillUser): ?string
    {
        return $skillUser->last_used_context;
    }

    public function endorsementsCount(SkillUser $skillUser): int
    {
        return $skillUser->endorsements_count;
    }

    public function endorsementProgressLabel(SkillUser $skillUser): string
    {
        return $skillUser->endorsementLabel();
    }

    public function isSoleEndorsement(SkillUser $skillUser): bool
    {
        return $skillUser->isSoleEndorsement();
    }

    public function endorsementMetalClasses(SkillUser $skillUser): string
    {
        return $skillUser->endorsementMetalClasses();
    }

    public function dormantBadgeClasses(SkillUser $skillUser): string
    {
        return $skillUser->dormantBadgeClasses();
    }

    public function endorsersAvatarStack(SkillUser $skillUser, int $limit = 5): Collection
    {
        $ids = collect(array_slice($skillUser->endorsers ?? [], 0, $limit));

        if ($ids->isEmpty()) return collect();

        $missing = $ids->diff(array_keys($this->userCache));

        if ($missing->isNotEmpty()) {
            User::whereIn('id', $missing)->get()->each(function (User $user) {
                $this->userCache[$user->id] = $user;
            });
        }

        return $ids->map(fn($id) => $this->userCache[$id] ?? null)->filter()->values();
    }

    public function isActive(SkillUser $skillUser): bool
    {
        return $skillUser->isActive(SkillUser::ACTIVE_WINDOW_DAYS);
    }

    public function isEndorsed(SkillUser $skillUser): bool
    {
        return $skillUser->isEndorsed(SkillUser::ENDORSEMENT_THRESHOLD);
    }

    public function isDormant(SkillUser $skillUser): bool
    {
        return $skillUser->isDormant();
    }

    public function lastUsedLabel(SkillUser $skillUser): string
    {
        if (!$skillUser->last_used_at) return 'هنوز فرصت به‌کارگیری آن پیش نیامده';

        return toJalaliRelative($skillUser->last_used_at);
    }

    public function mentorLabel(SkillUser $skillUser): string
    {
        return $skillUser->is_mentoring ? 'آماده راهنمایی' : '';
    }

    public function preloadEndorsers(iterable $skillUsers, int $limit = 5): void
    {
        $ids = collect($skillUsers)
            ->flatMap(fn(SkillUser $row) => array_slice($row->endorsers ?? [], 0, $limit))
            ->unique()
            ->diff(array_keys($this->userCache))
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        User::whereIn('id', $ids)->get()->each(function (User $user) {
            $this->userCache[$user->id] = $user;
        });
    }

    public function privacyLabel(SkillUser $skillUser): string
    {
        return $skillUser->is_private ? 'خصوصی' : 'عمومی';
    }

    public function stateTier(SkillUser $skillUser): SkillTier
    {
        return $skillUser->stateTier();
    }
}
