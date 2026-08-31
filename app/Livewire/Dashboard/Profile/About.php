<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Actions\SaveAboutAction;
use App\Livewire\Dashboard\Profile\Forms\AboutForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class About extends Component
{
    public AboutForm $form;
    public array $extraAnswers = [];
    private const CORE_KEYS = ['bio', 'movies', 'music', 'hobbies', 'food', 'sports'];

    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.about-placeholder');
    }


    public function mount(): void
    {
        $profile = Auth::user()->profile;
        $aboutMe = $profile?->about_me ?? [];

        $safeAboutMe = collect($aboutMe)->filter(fn ($v) => !is_array($v))->toArray();
        if ($safeAboutMe) $this->form->fill($safeAboutMe);

        $this->extraAnswers = collect($aboutMe)
            ->filter(fn ($v, $k) => filled($k) && !is_array($v))
            ->except(self::CORE_KEYS)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.profile.about');
    }

    public function save(SaveAboutAction $action): void
    {
        $action->execute($this->form, $this->extraAnswers);

        $this->dispatch('toast', message: 'اطلاعات درباره من با موفقیت بروزرسانی شد', type: 'success');
    }
}
