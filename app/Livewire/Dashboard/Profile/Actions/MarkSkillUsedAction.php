<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\SkillRequestResource;
use App\Models\SkillUser;

class MarkSkillUsedAction
{
    public function execute(SkillUser $skillUser, ?string $context): SkillUser
    {
        $row = SkillUser::findOrFail($skillUser->id);

        if ($row->user_id !== auth()->id() && !SkillRequestResource::canEdit($row)) {
            abort(403);
        }

        if ($row->status !== SkillRequestStatus::Approved) {
            throw new \RuntimeException('فقط مهارت‌های تأییدشده را می‌توان به‌روزرسانی کرد.');
        }

        if ($context !== null && mb_strlen($context) > 255) {
            throw new \RuntimeException('توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.');
        }

        $row->last_used_at = now();
        $row->last_used_context = $context;
        $row->save();

        return $row;
    }
}
