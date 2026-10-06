<?php

namespace App\View\Composers;

use App\Models\Onboarding;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AccountOnboardingComposer
{
    public function compose(View $view): void
    {
        $exclusiveOnboarding = auth()->check()
            ? Cache::remember(
                ModelCacheVersion::key(Onboarding::class, 'exclusive:' . auth()->id()),
                now()->addDay(),
                fn() => Onboarding::where('user_id', auth()->id())->where('is_active', true)->first(['updated_at']),
            )
            : null;

        $onboardingHighlighted = (bool) ($exclusiveOnboarding?->updated_at && $exclusiveOnboarding->updated_at->diffInDays(now()) <= 7);

        $view->with('exclusiveOnboarding', $exclusiveOnboarding)->with('onboardingHighlighted', $onboardingHighlighted);
    }
}
