<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Filament\Resources\SkillRequestResource;
use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeEndorsementAction
{
    public function execute(SkillUser $skillUser, User $endorser): SkillUser
    {
        if ($endorser->id !== auth()->id() && !SkillRequestResource::canEdit($skillUser)) {
            abort(403);
        }

        return DB::transaction(function () use ($skillUser, $endorser) {
            $row = SkillUser::lockForUpdate()->findOrFail($skillUser->id);

            if ($row->hasEndorser($endorser->id)) {
                $remaining = array_values(array_diff($row->endorsers ?? [], [$endorser->id]));
                $row->endorsers = $remaining;
                $row->endorsements_count = count($remaining);
                $row->save();
            }

            return $row;
        });
    }
}
