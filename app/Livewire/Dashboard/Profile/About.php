<?php

namespace App\Livewire\Dashboard\Profile;

use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class About extends Component
{
    #[Validate('nullable|string|max:2000', message: ['max' => 'متن درباره من نباید بیشتر از ۲۰۰۰ کاراکتر باشد.'])]
    public $bio = '';

    #[Validate('nullable|string|max:255', message: ['max' => 'متن فیلم و سریال نباید بیشتر از ۲۵۵ کاراکتر باشد.'])]
    public $movies = '';

    #[Validate('nullable|string|max:255', message: ['max' => 'متن موسیقی و پادکست نباید بیشتر از ۲۵۵ کاراکتر باشد.'])]
    public $music = '';

    #[Validate('nullable|string|max:255', message: ['max' => 'متن سرگرمی‌ها نباید بیشتر از ۲۵۵ کاراکتر باشد.'])]
    public $hobbies = '';

    #[Validate('nullable|string|max:255', message: ['max' => 'متن غذا و خوراکی نباید بیشتر از ۲۵۵ کاراکتر باشد.'])]
    public $food = '';

    #[Validate('nullable|string|max:255', message: ['max' => 'متن ورزش و تفریح نباید بیشتر از ۲۵۵ کاراکتر باشد.'])]
    public $sports = '';

    public function mount()
    {
        $profile = Auth::user()->profile;

        if ($profile?->about_me) {
            $this->fill($profile->about_me);
        }
    }

    public function save()
    {
        $this->validate();

        Profile::updateOrCreate(
            ['user_id' => Auth::id()],
            ['about_me' => $this->only(['bio', 'movies', 'music', 'hobbies', 'food', 'sports'])]
        );

        $this->dispatch('toast', message: 'اطلاعات درباره من با موفقیت بروزرسانی شد', type: 'success');
    }

    public function render()
    {
        return view('livewire.dashboard.profile.about');
    }
}
