<?php

namespace App\Livewire\Dashboard\Profile;

use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class About extends Component
{
    public $bio;
    public $movies;
    public $music;
    public $hobbies;
    public $food;
    public $sports;

    public function mount()
    {
        $profile = Auth::user()->profile;
        if ($profile && $profile->about_me) {
            $about = $profile->about_me;
            $this->bio = $about['bio'] ?? '';
            $this->movies = $about['movies'] ?? '';
            $this->music = $about['music'] ?? '';
            $this->hobbies = $about['hobbies'] ?? '';
            $this->food = $about['food'] ?? '';
            $this->sports = $about['sports'] ?? '';
        }
    }

    public function save()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = new Profile(['user_id' => $user->id]);
        }

        $profile->about_me = [
            'bio' => $this->bio,
            'movies' => $this->movies,
            'music' => $this->music,
            'hobbies' => $this->hobbies,
            'food' => $this->food,
            'sports' => $this->sports,
        ];

        $profile->save();

        $this->dispatch('toast', message: 'اطلاعات درباره من با موفقیت بروزرسانی شد', type: 'success');
    }

    public function render()
    {
        return view('livewire.dashboard.profile.about');
    }
}
