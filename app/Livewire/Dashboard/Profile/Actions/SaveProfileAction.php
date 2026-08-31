<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\ProfileForm;
use App\Models\Profile;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SaveProfileAction
{
    use CleansAttachedFiles, StoresAttachedFiles;
    public function execute(ProfileForm $form): array
    {
        $form->validate();

        $user = Auth::user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $profile->fill($form->getProfileData());

        if ($form->birthYear && $form->birthMonth && $form->birthDay) {
            try {
                $profile->birthdate = (new Jalalian(
                    $form->birthYear,
                    $form->birthMonth,
                    $form->birthDay
                ))->toCarbon();
            } catch (\Throwable) {
                $profile->birthdate = null;
            }
        } else {
            $profile->birthdate = null;
        }

        if (!$user->profile || !$profile->department_id) $profile->department_id = 'HR';

        $oldImage = $profile->image;
        $imagePath = null;
        if ($form->image) {
            $imagePath = static::storeAttachment($form->image, 'profiles/images')['path'];
            $profile->image = $imagePath;
        }

        $profile->favorite_colors = $form->favoriteColors;

        try {
            $profile->save();
        } catch (\Throwable $e) {
            if ($imagePath) {
                static::deleteStoredFiles($imagePath);
            }

            throw $e;
        }

        if ($imagePath && $oldImage) {
            static::deleteStoredFiles($oldImage);
        }

        $user->setRelation('profile', $profile);

        return ['profile' => $profile, 'imagePath' => $imagePath];
    }
}
