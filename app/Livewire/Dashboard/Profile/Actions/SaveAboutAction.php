<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\AboutForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class SaveAboutAction
{
    /**
     * Execute the about me save operation.
     */
    public function execute(AboutForm $form, array $extra = []): Profile
    {
        $form->validate();

        return Profile::updateOrCreate(
            ['user_id' => Auth::id()],
            ['about_me' => array_merge($form->getAboutMeData(), $extra)]
        );
    }
}
