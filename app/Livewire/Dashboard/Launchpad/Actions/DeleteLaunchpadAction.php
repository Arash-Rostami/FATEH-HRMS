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
        $fresh = Launchpad::query()->find($row->id);

        if ($fresh === null) {
            return;
        }

        if (!LaunchpadAccessPolicy::canManage($fresh, $user)) {
            throw new RuntimeException('این عملیات مجاز نیست.');
        }

        $fresh->delete();
    }
}
