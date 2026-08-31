<?php

namespace App\Models\Concerns;

trait HasStageHelpers
{
    public function syncStage(): void
    {
        if (!$this->relationLoaded('reviews')) $this->load('reviews');

        $reviews = $this->reviews;

        $deptCodes = (array)($this->departments ?? []);
        if (empty($deptCodes)) {
            $this->updateStageIfChanged('pending');
            return;
        }

        $myDept = $deptCodes[0];

        $maReview = null;
        $otherDepWithValidFeedBack = [];

        foreach ($reviews as $review) {
            $deptId = $review->department_id;
            if (in_array($review->feedback, ['agree', 'disagree', 'neutral'])) $otherDepWithValidFeedBack[$deptId] = true;

            if ($deptId === 'MA') $maReview = $review;
        }

        $hasMyDeptReview = isset($otherDepWithValidFeedBack[$myDept]);

        // Check if ALL departments have been reviewed
        $allDeptsReviewed = collect($deptCodes)->every(fn($code) => isset($otherDepWithValidFeedBack[$code]));


        $newStage = match (true) {
            !$hasMyDeptReview => 'team_remarks',
            !$allDeptsReviewed => 'dept_remarks',
            !$maReview => 'awaiting_decision',
            default => match ($maReview->feedback) {
                'agree' => $this->resolveAcceptedStage($reviews, $maReview),
                'disagree' => 'rejected',
                'incomplete' => 'under_review',
                default => 'awaiting_decision',
            },
        };

        $this->updateStageIfChanged($newStage);
    }

    private function resolveAcceptedStage($reviews, $maReview): string
    {
        $referrals = (array) ($maReview->referral ?? []);
        if (empty($referrals)) return 'accepted';

        $completedDepts = $reviews
            ->whereIn('department_id', $referrals)
            ->where('complete', true)
            ->pluck('department_id')
            ->all();

        return empty(array_diff($referrals, $completedDepts)) ? 'closed' : 'accepted';
    }


    private function updateStageIfChanged(string $newStage): void
    {
        $currentStage = $this->getAttribute('stage');

        if ($currentStage === $newStage) return;

        $this->forceFill(['stage' => $newStage])->saveQuietly();
    }
}
