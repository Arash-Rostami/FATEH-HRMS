<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Jobs\RefreshLaunchpadJob;
use App\Models\Launchpad;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Support\Facades\DB;

class ActivateLaunchpadAction
{
    public function execute(int $id, User $user): string
    {
        $armed = false;
        $row = DB::transaction(function () use ($id, $user, &$armed): Launchpad|string {
            $row = Launchpad::query()->whereKey($id)->lockForUpdate()->first(['id', 'user_id', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'refresh_due_at']);

            if ($row === null || !LaunchpadAccessPolicy::canManage($row, $user)) {
                return 'denied';
            }

            $today = today();

            if ($row->accessible_to?->lt($today) === true || $row->expire_at?->lt($today) === true) {
                return 'expired';
            }

            $armed = $row->refresh_due_at !== null && !$row->isLive();

            $row->update([
                'active' => true,
                'accessible_from' => $row->accessible_from === null || $row->accessible_from->gt($today) ? $today : $row->accessible_from,
                ...($armed ? ['refresh_due_at' => now()] : []),
            ]);

            return $row;
        });

        if (is_string($row)) {
            return $row;
        }

        if (filled($row->audience)) {
            StateService::flush();
        }

        if ($armed) {
            RefreshLaunchpadJob::dispatch((int) $row->id);
        }

        return 'ok';
    }
}
