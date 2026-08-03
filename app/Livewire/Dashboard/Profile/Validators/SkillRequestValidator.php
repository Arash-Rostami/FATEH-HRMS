<?php

namespace App\Livewire\Dashboard\Profile\Validators;

use App\Enums\SkillRequestStatus;
use App\Livewire\Dashboard\Profile\Forms\SkillsForm;
use App\Models\Skill;
use App\Models\SkillUser;

class SkillRequestValidator
{
    public function validate(SkillsForm $form, int $userId): void
    {
        if (blank($form->skillId) && blank($form->proposedName)) {
            throw new \RuntimeException('یک مهارت را از فهرست انتخاب کنید یا مهارت جدید پیشنهاد دهید.');
        }

        if (filled($form->skillId)) {
            $skill = Skill::find($form->skillId);

            if (!$skill || !$skill->is_active || $skill->is_ghost) {
                throw new \RuntimeException('مهارت انتخاب‌شده در دسترس نیست.');
            }

            $this->guardExistingRow($userId, $skill->id);

            return;
        }

        $name = trim(preg_replace('/\s+/', ' ', (string) $form->proposedName));

        if ($name === '' || mb_strlen($name) > 255) {
            throw new \RuntimeException('نام مهارت پیشنهادی معتبر نیست.');
        }

        $existingActive = Skill::matchingName($name)->where('is_active', true)->where('is_ghost', false)->first();

        if ($existingActive) {
            throw new \RuntimeException('این مهارت در فهرست موجود است، لطفاً از فهرست انتخاب کنید.');
        }

        $candidate = Skill::matchingName($name)->first();

        if ($candidate) {
            $this->guardExistingRow($userId, $candidate->id);
        }
    }

    private function guardExistingRow(int $userId, int $skillId): void
    {
        $row = SkillUser::where('user_id', $userId)->where('skill_id', $skillId)->first();

        if (!$row) {
            return;
        }

        if ($row->status === SkillRequestStatus::Pending) {
            throw new \RuntimeException('درخواست این مهارت در حال بررسی است.');
        }

        if ($row->status === SkillRequestStatus::Approved) {
            throw new \RuntimeException('این مهارت پیش‌تر برای شما تأیید شده است.');
        }
    }
}
