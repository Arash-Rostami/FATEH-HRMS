<?php

namespace App\Services;

use App\Enums\SkillRequestStatus;
use App\Models\SkillUser;
use Illuminate\Support\Facades\DB;

class SkillService
{
    public function approve(SkillUser $row): SkillUser
    {
        $dispatch = false;

        $row = DB::transaction(function () use ($row, &$dispatch) {
            $row = SkillUser::with('skill')->lockForUpdate()->find($row->id);

            if ($row->status !== SkillRequestStatus::Pending) {
                return $row;
            }

            $row->status = SkillRequestStatus::Approved;
            $row->approved_at = now();
            $row->approved_by = auth()->id();
            $row->rejected_reason = null;
            $row->save();

            if ($row->requested_name !== null) {
                $row->skill->activate();
            }

            $dispatch = true;

            return $row;
        });

        if ($dispatch) {
            SkillUser::notify(
                (int) $row->user_id,
                "skills.skill_user.{$row->id}.approved.{$row->approved_at->timestamp}",
                __('resources/skill_request/strings.notifications.approved', ['skill' => $row->skill->name])
            );
        }

        return $row;
    }

    public function reject(SkillUser $row, ?string $reason = null): SkillUser
    {
        $dispatch = false;

        $row = DB::transaction(function () use ($row, $reason, &$dispatch) {
            $row = SkillUser::with('skill')->lockForUpdate()->find($row->id);

            if ($row->status !== SkillRequestStatus::Pending) {
                return $row;
            }

            $row->status = SkillRequestStatus::Rejected;
            $row->rejected_reason = $reason;
            $row->approved_at = null;
            $row->approved_by = null;
            $row->save();

            $dispatch = true;

            return $row;
        });

        if ($dispatch) {
            SkillUser::notify(
                (int) $row->user_id,
                "skills.skill_user.{$row->id}.rejected.{$row->updated_at->timestamp}",
                __('resources/skill_request/strings.notifications.rejected', ['skill' => $row->skill->name]),
                'warning'
            );
        }

        return $row;
    }
}
