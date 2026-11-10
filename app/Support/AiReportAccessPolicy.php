<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\AiReport;
use App\Models\User;
use App\Services\AiReport\ReportAiService;

final class AiReportAccessPolicy
{
    public static function canView(AiReport $report, ?User $user): bool
    {
        return $user !== null
            && ($report->isOwnedBy($user)
                || ($report->active
                    && ($user->hasElevatedRole()
                        || ($report->isRecipient($user) && $user->isActive() && $report->saved_at !== null && $report->status === AiReport::STATUS_READY))));
    }

    public static function canGenerate(?User $user): bool
    {
        return $user !== null
            && $user->isActive()
            && $user->type !== UserType::Guest->value
            && ReportAiService::isConfigured();
    }

    public static function canRefine(AiReport $report, ?User $user): bool
    {
        return $report->isOwnedBy($user)
            && $report->active
            && in_array($report->status, [AiReport::STATUS_READY, AiReport::STATUS_FAILED], true);
    }

    public static function canSave(AiReport $report, ?User $user): bool
    {
        return $report->isOwnedBy($user) && $report->status === AiReport::STATUS_READY;
    }

    public static function canShare(AiReport $report, ?User $user): bool
    {
        return self::canSave($report, $user) && $report->active;
    }

    public static function canRename(AiReport $report, ?User $user): bool
    {
        return self::canSave($report, $user) && $report->active && empty($report->recipients);
    }

    public static function canRevert(AiReport $report, ?User $user): bool
    {
        return self::canSave($report, $user) && $report->active && empty($report->recipients);
    }

    public static function canDelete(AiReport $report, ?User $user): bool
    {
        return $report->isOwnedBy($user) && $report->status !== AiReport::STATUS_GENERATING;
    }

    public static function canToggleActive(AiReport $report, ?User $user): bool
    {
        return self::canDelete($report, $user);
    }
}
