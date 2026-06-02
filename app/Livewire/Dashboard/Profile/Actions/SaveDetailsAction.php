<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class SaveDetailsAction
{
    public function execute(DetailsForm $form): Profile
    {
        $form->normalize();
        $form->validate();

        $profile = Auth::user()->profile;

        $profile->syncDetails($form->values);

        return $profile;
    }
}
