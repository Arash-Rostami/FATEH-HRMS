<?php

namespace App\Livewire\Dashboard\Profile\Validators;

use App\Enums\SkillRequestStatus;
use App\Models\SkillUser;
use App\Models\User;

class EndorsementValidator
{
    public function validate(SkillUser $skillUser, User $endorser): void
    {
        if ($skillUser->user_id === $endorser->id) {
            throw new \RuntimeException('امکان تأیید مهارت خودتان وجود ندارد.');
        }

        if ($skillUser->status !== SkillRequestStatus::Approved) {
            throw new \RuntimeException('این مهارت هنوز تأیید نشده است.');
        }

        if ($skillUser->hasEndorser($endorser->id)) {
            throw new \RuntimeException('شما قبلاً این مهارت را تأیید کرده‌اید.');
        }

        if ($skillUser->is_private) {
            throw new \RuntimeException('این مهارت خصوصی است و قابل تأیید نیست.');
        }
    }
}
