<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Filament\Resources\SkillRequestResource;
use App\Livewire\Dashboard\Profile\Validators\EndorsementValidator;
use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EndorseSkillAction
{
    public function __construct(private EndorsementValidator $validator)
    {
    }

    public function execute(SkillUser $skillUser, User $endorser): SkillUser
    {
        if ($endorser->id !== auth()->id() && !SkillRequestResource::canEdit($skillUser)) {
            abort(403);
        }

        $row = DB::transaction(function () use ($skillUser, $endorser) {
            $row = SkillUser::lockForUpdate()->findOrFail($skillUser->id);

            $this->validator->validate($row, $endorser);

            $endorsers = array_values(array_unique([...($row->endorsers ?? []), $endorser->id]));
            $row->endorsers = $endorsers;
            $row->endorsements_count = count($endorsers);
            $row->save();

            return $row;
        });

        SkillUser::notify(
            (int) $row->user_id,
            "skills.endorsement.{$row->id}.{$endorser->id}.{$row->updated_at->timestamp}",
            'مهارت شما توسط یکی از همکاران تأیید شد.'
        );

        return $row;
    }
}
