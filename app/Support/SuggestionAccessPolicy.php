<?php

namespace App\Support;

use App\Models\Review;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Auth;

final class SuggestionAccessPolicy
{
    public static function canDecide(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        return $user?->isSeniorDecisionMaker() && $suggestion?->stage === 'awaiting_decision';
    }

    public static function canGiveFeedback(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        if (!$user || !$user->isDeptHead() || $user->isTopExecutive() || !$suggestion) return false;

        $deptId = $user->profile?->department_id;
        if (!$deptId) return false;

        $stageOk = match ($suggestion->stage) {
            'team_remarks' => $deptId === ($suggestion->departments[0] ?? null),
            'dept_remarks' => in_array($deptId, $suggestion->departments ?? [], true),
            default => false,
        };
        if (!$stageOk) return false;

        $feedback = self::departmentReview($suggestion, $deptId)?->feedback;
        return !in_array($feedback, ['agree', 'disagree', 'neutral'], true);
    }

    public static function canMarkComplete(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        if (!$user?->isDeptHead() || $user->isTopExecutive()) return false;

        $deptId = $user->profile?->department_id;
        if (!$deptId) return false;

        $ceoReview = self::ceoReview($suggestion);
        if (!in_array($deptId, $ceoReview?->referral ?? [], true)) return false;

        $deptReview = self::departmentReview($suggestion, $deptId);
        return $deptReview !== null && !$deptReview->isComplete();
    }

    public static function ceoReview(?Suggestion $suggestion): ?Review
    {
        return $suggestion?->reviews->firstWhere('department_id', 'MA');
    }

    public static function departmentReview(?Suggestion $suggestion, string $deptId): ?Review
    {
        return $suggestion?->reviews->firstWhere('department_id', $deptId);
    }

    public static function buildReviewRows(
        int $suggestionId,
        array $departments,
        ?string $homeDept,
        int $submitterUserId,
        bool $submitterIsManager,
        bool $selfFill,
        string $homeFeedback = '',
        string $homeComment = '',
        array $deptFeedback = [],
        array $deptComments = [],
    ): array {
        $now = now();
        $base = [
            'suggestion_id' => $suggestionId,
            'user_id'       => $submitterUserId,
            'complete'      => false,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $rows = [array_merge($base, [
            'department_id' => $homeDept,
            'feedback'      => $homeFeedback !== '' ? $homeFeedback : ($submitterIsManager ? 'agree' : 'unknown'),
            'comments'      => $homeComment !== '' ? $homeComment : ($submitterIsManager
                ? 'توجه به اینکه پیشنهاد دهنده هستم، نیاز به بررسی و اعلام نظر از سمت مدیریت'
                : null),
        ])];

        if ($selfFill) {
            foreach ($departments as $dept) {
                if ($dept === $homeDept) continue;
                $rows[] = array_merge($base, [
                    'department_id' => $dept,
                    'feedback'      => $deptFeedback[$dept] ?? 'unknown',
                    'comments'      => $deptComments[$dept] ?? '',
                ]);
            }
        }

        return $rows;
    }
}
