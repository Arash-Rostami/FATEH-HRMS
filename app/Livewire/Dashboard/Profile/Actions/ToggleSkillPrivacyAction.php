<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\SkillRequestResource;
use App\Models\SkillUser;

class ToggleSkillPrivacyAction
{
    public function execute(SkillUser $skillUser): SkillUser
    {
        $row = SkillUser::findOrFail($skillUser->id);

        if ($row->user_id !== auth()->id() && !SkillRequestResource::canEdit($row)) {
            abort(403);
        }

        if ($row->status !== SkillRequestStatus::Approved) {
            throw new \RuntimeException('فقط مهارت‌های تأییدشده امکان تغییر حریم خصوصی دارند.');
        }

        $row->is_private = !$row->is_private;
        $row->save();

        return $row;
    }
}
