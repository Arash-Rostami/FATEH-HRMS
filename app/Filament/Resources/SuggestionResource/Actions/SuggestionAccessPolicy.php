<?php

namespace App\Filament\Resources\SuggestionResource\Actions;

use App\Models\Review;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Auth;

class SuggestionAccessPolicy
{
    public static function canDecide(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        if (!$user || !$user->isCeo()) return false;

        return $suggestion?->stage === 'awaiting_decision';
    }

    public static function canGiveFeedback(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        if (!$user || !$user->isDeptHead() || $user->isCeo()) return false;
        if (!$suggestion || $suggestion->stage !== 'dept_remarks') return false;

        $deptId = $user->profile?->department_id;
        if (!$deptId || !in_array($deptId, $suggestion->departments ?? [], true)) return false;

        $review = self::departmentReview($suggestion, $deptId);
        if (!$review) return true;

        return !in_array($review->feedback, ['agree', 'disagree', 'neutral'], true);
    }

    public static function canMarkComplete(?Suggestion $suggestion): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        $deptId = $user->profile?->department_id;
        if (!$deptId) return false;

        $ceoReview = self::ceoReview($suggestion);
        if (!$ceoReview) return false;

        $referrals = $ceoReview->referral ?? [];
        if (!in_array($deptId, $referrals, true)) return false;

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
}
