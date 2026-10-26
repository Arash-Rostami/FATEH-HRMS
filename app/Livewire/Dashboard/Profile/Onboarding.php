<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Presentation\OnboardingPresenter;
use App\Models\Onboarding as OnboardingModel;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Onboarding extends Component
{
    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.onboarding.placeholder');
    }

    #[Computed]
    public function onboarding(): ?OnboardingModel
    {
        $userId = auth()->id();

        return OnboardingModel::visibleTo($userId)
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId])
            ->first();
    }

    public function render()
    {
        return view('livewire.dashboard.profile.onboarding', [
            'onboarding' => $this->onboarding,
            'presenter' => new OnboardingPresenter(),
        ]);
    }
}
