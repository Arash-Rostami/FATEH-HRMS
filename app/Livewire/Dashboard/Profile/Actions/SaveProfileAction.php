<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\ProfileForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SaveProfileAction
{
    /**
     * Execute the profile save operation.
     *
     * @param ProfileForm $form
     * @return array{profile: Profile, imagePath: string|null}
     */
    public function execute(ProfileForm $form): array
    {
        $form->validate();

        $user = Auth::user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        // Fill core profile data
        $profile->fill($form->getProfileData());

        // Handle birthdate conversion (Jalali to Carbon)
        if ($form->birthYear && $form->birthMonth && $form->birthDay) {
            $profile->birthdate = Jalalian::fromFormat(
                'Y/n/j',
                "{$form->birthYear}/{$form->birthMonth}/{$form->birthDay}"
            )->toCarbon();
        }

        // Handle image upload
        $imagePath = null;
        if ($form->image) {
            $imagePath = $form->image->store('profiles', 'public');
            $profile->image = $imagePath;
        }

        // Handle favorite colors
        $profile->favorite_colors = $form->favoriteColors;

        $profile->save();

        return [
            'profile' => $profile,
            'imagePath' => $imagePath,
        ];
    }
}
