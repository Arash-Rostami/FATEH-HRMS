<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Models\Profile;
use App\Traits\CleansAttachedFiles;
use Illuminate\Support\Facades\Auth;

class DeleteProfileImageAction
{
    use CleansAttachedFiles;
    public function execute(): bool
    {
        $profile = Auth::user()->profile;

        if (!$profile || !$profile->image) {
            return false;
        }

        static::deleteStoredFiles($profile->image);

        $profile->image = null;
        $profile->save();

        return true;
    }
}
