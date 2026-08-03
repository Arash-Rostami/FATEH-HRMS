<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\SkillRequestResource;
use App\Models\SkillUser;

class ToggleMentoringAction
{
    public function execute(SkillUser $skillUser): SkillUser
    {
        $row = SkillUser::findOrFail($skillUser->id);

        if ($row->user_id !== auth()->id() && !SkillRequestResource::canEdit($row)) {
            abort(403);
        }

        if ($row->status !== SkillRequestStatus::Approved) {
            throw new \RuntimeException('فقط مهارت‌های تأییدشده امکان راهنمایی دارند.');
        }

        $row->is_mentoring = !$row->is_mentoring;
        $row->save();

        return $row;
    }
}
