<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Actions\SaveAboutAction;
use App\Livewire\Dashboard\Profile\Forms\AboutForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class About extends Component
{
    public AboutForm $form;

    public function mount(): void
    {
        $profile = Auth::user()->profile;

        if ($profile?->about_me) $this->form->fill($profile->about_me);
    }

    public function render()
    {
        return view('livewire.dashboard.profile.about');
    }

    public function save(SaveAboutAction $action): void
    {
        $action->execute($this->form);

        $this->dispatch('toast', message: 'اطلاعات درباره من با موفقیت بروزرسانی شد', type: 'success');
    }
}
