<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Presentation\OnboardingPresenter;
use App\Models\Onboarding as OnboardingModel;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Onboarding extends Component
{
    #[Computed(persist: true)]
    public function onboarding(): ?OnboardingModel
    {
        $userId = auth()->id();

        return OnboardingModel::where('is_active', true)
            ->where(fn($q) => $q->where('user_id', $userId)->orWhereNull('user_id'))
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
