<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Support\Facades\DB;

class DeactivateLaunchpadAction
{
    public function execute(int $id, User $user): bool
    {
        $row = DB::transaction(function () use ($id, $user): ?Launchpad {
            $row = Launchpad::query()->whereKey($id)->lockForUpdate()->first(['id', 'user_id', 'active', 'audience']);

            if ($row === null || !LaunchpadAccessPolicy::canManage($row, $user)) {
                return null;
            }

            $row->update(['active' => false]);

            return $row;
        });

        if ($row === null) {
            return false;
        }

        if (filled($row->audience)) {
            StateService::flush();
        }

        return true;
    }
}
