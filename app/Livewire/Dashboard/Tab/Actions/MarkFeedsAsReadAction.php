<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Feed;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class MarkFeedsAsReadAction
{
    public function execute(int $userId): int
    {
        $count = Feed::markAllReadFor($userId);

        DB::afterCommit(fn () => StateService::flush());

        return $count;
    }
}
