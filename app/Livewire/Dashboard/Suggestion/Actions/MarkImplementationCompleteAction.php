<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Models\Review;
use App\Models\Suggestion;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Auth;

class MarkImplementationCompleteAction
{
    public function execute(Suggestion $suggestion): ?Review
    {
        $deptId = Auth::user()?->profile?->department_id;
        if (!$deptId) return null;

        $ceoReview = $suggestion->reviews->firstWhere('department_id', 'MA');
        if (!$ceoReview || !in_array($deptId, $ceoReview->referral ?? [], true)) return null;

        $review = $suggestion->reviews->firstWhere('department_id', $deptId);
        if (!$review || $review->isComplete()) return $review;

        $review->complete = true;
        $review->save();

        $suggestion->load('reviews')->syncStage();

        StateService::flush();

        return $review->refresh();
    }
}
