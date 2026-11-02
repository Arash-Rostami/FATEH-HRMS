<?php

namespace App\View\Composers;

use App\Livewire\Dashboard\Profile\Presentation\ProfilePresenter;
use App\Models\Onboarding;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AccountOnboardingComposer
{
    public function __construct(private ProfilePresenter $presenter) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $onboarding = $user ? $this->exclusiveOnboarding($user) : null;

        $view->with([
            'exclusiveOnboarding' => $onboarding,
            'onboardingHighlighted' => $onboarding?->updated_at?->gte(now()->subDays(7)) ?? false,
            'profileCompletion' => $user ? $this->presenter->completion($user) : 100,
        ]);
    }

    private function exclusiveOnboarding(User $user): ?Onboarding
    {
        return Cache::remember(
            ModelCacheVersion::key(Onboarding::class, "exclusive:{$user->id}"),
            now()->addDay(),
            fn () => Onboarding::whereBelongsTo($user)->where('is_active', true)->first(['updated_at']),
        );
    }
}
