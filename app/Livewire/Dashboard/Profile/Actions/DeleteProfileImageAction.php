<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DeleteProfileImageAction
{
    /**
     * Delete the user's profile image.
     *
     * @return bool True if image was deleted, false if no image existed
     */
    public function execute(): bool
    {
        $profile = Auth::user()->profile;

        if (!$profile || !$profile->image) {
            return false;
        }

        Storage::disk('public')->delete($profile->image);

        $profile->image = null;
        $profile->save();

        return true;
    }
}
