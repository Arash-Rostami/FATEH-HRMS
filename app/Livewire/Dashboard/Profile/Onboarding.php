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
        return OnboardingModel::where('is_active', true)->first();
    }

    public function render()
    {
        return view('livewire.dashboard.profile.onboarding', [
            'onboarding' => $this->onboarding,
            'presenter'  => new OnboardingPresenter(),
        ]);
    }
}
