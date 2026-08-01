<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\AboutForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class SaveAboutAction
{
    public function execute(AboutForm $form, array $extra = []): Profile
    {
        $form->validate();

        $extra = collect($extra)
            ->filter(fn($v, $k) => filled($k) && !is_array($v))
            ->toArray();

        return Profile::updateOrCreate(
            ['user_id' => Auth::id()],
            ['about_me' => array_merge($extra, $form->getAboutMeData())]
        );
    }
}
