<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use RuntimeException;

class DeleteLaunchpadAction
{
    public function execute(Launchpad $row, User $user): void
    {
        if (!LaunchpadAccessPolicy::canManage($row, $user)) {
            throw new RuntimeException('این عملیات مجاز نیست.');
        }

        $row->delete();
    }
}
