<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Enums\SkillRequestStatus;
use App\Livewire\Dashboard\Profile\Forms\SkillsForm;
use App\Livewire\Dashboard\Profile\Validators\SkillRequestValidator;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequestSkillAction
{
    public function __construct(private SkillRequestValidator $validator)
    {
    }

    public function execute(SkillsForm $form): SkillUser
    {
        $form->validate();

        $userId = (int) auth()->id();

        $this->validator->validate($form, $userId);

        return DB::transaction(function () use ($form, $userId) {
            if (filled($form->skillId)) {
                $skill = Skill::lockForUpdate()->findOrFail($form->skillId);
                $requestedName = null;
            } else {
                $name = trim(preg_replace('/\s+/', ' ', (string) $form->proposedName));
                $skill = $this->resolveOrCreateDraft($name);
                $requestedName = $name;
            }

            $row = SkillUser::lockForUpdate()
                ->where('user_id', $userId)
                ->where('skill_id', $skill->id)
                ->first();

            if ($row) {
                if ($row->status !== SkillRequestStatus::Rejected) {
                    throw new \RuntimeException('این مهارت در وضعیت قابل درخواست مجدد نیست.');
                }

                $row->status = SkillRequestStatus::Pending;
                $row->rejected_reason = null;
                $row->requested_name = $requestedName;
                $row->approved_at = null;
                $row->approved_by = null;
                $row->created_at = now();
                $row->save();

                return $row;
            }

            return SkillUser::create([
                'user_id' => $userId,
                'skill_id' => $skill->id,
                'status' => SkillRequestStatus::Pending,
                'requested_name' => $requestedName,
            ]);
        });
    }

    private function resolveOrCreateDraft(string $name): Skill
    {
        $skill = Skill::matchingName($name)->lockForUpdate()->first();

        if ($skill) {
            if ($skill->is_ghost) {
                $skill->is_ghost = false;
                $skill->save();
            }

            return $skill;
        }

        try {
            return Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => false]);
        } catch (QueryException $e) {
            if (!Str::contains($e->getMessage(), ['Duplicate', '1062'])) {
                throw $e;
            }

            $skill = Skill::matchingName($name)->lockForUpdate()->first();

            if (!$skill) {
                throw $e;
            }

            if ($skill->is_ghost) {
                $skill->is_ghost = false;
                $skill->save();
            }

            return $skill;
        }
    }
}
