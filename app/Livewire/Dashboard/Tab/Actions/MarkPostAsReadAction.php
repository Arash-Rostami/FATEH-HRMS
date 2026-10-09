<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Post;
use App\Services\Menu\NudgeService;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class MarkPostAsReadAction
{
    public function execute(int $postId, int $userId): int
    {
        $count = Post::markReadFor($postId, $userId);

        DB::afterCommit(fn () => StateService::flush());

        if ($count > 0) {
            NudgeService::refreshBell();
        }

        return $count;
    }
}