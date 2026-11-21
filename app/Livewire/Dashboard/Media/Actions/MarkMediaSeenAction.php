<?php

namespace App\Livewire\Dashboard\Media\Actions;

use App\Models\Media;
use App\Models\User;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class MarkMediaSeenAction
{
    public static function execute(User $user): int
    {
        $count = Media::unseenBy($user->id)->update([
            'seen_by' => DB::raw("JSON_ARRAY_APPEND(COALESCE(`seen_by`, JSON_ARRAY()), '$', ".(int) $user->id.')'),
        ]);

        DB::afterCommit(fn () => StateService::flush());

        return $count;
    }
}
