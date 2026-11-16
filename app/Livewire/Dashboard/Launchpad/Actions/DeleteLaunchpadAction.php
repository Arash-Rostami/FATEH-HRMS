<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteLaunchpadAction
{
    public function execute(Launchpad $row, User $user): void
    {
        DB::transaction(function () use ($row, $user) {
            $fresh = Launchpad::query()->whereKey($row->id)->lockForUpdate()->first();
            if (!$fresh || !LaunchpadAccessPolicy::canManage($fresh, $user)) {
                throw new RuntimeException('این عملیات مجاز نیست.');
            }
            $fresh->delete();
        });
    }
}
