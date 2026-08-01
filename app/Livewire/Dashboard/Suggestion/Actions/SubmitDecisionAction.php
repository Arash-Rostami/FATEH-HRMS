<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Support\SuggestionAccessPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmitDecisionAction
{
    public function execute(DecisionForm $form, Suggestion $suggestion): void
    {
        abort_unless(SuggestionAccessPolicy::canDecide($suggestion), 403);

        $form->validate();

        DB::transaction(function () use ($form, $suggestion): void {
            $suggestion->comments = $form->decisionComment;
            $suggestion->save();

            $isAccepted = $form->decision === 'accepted';

            Review::updateOrCreate(
                ['suggestion_id' => $suggestion->id, 'department_id' => 'MA'],
                [
                    'feedback' => match ($form->decision) {
                        'accepted'     => 'agree',
                        'rejected'     => 'disagree',
                        'under_review' => 'incomplete',
                    },
                    'comments' => $form->decisionComment,
                    'referral' => $isAccepted ? $form->referralDepts : null,
                    'actions'  => $isAccepted ? $form->referralActions : null,
                    'user_id'  => Auth::id(),
                    'complete' => false,
                ]
            );

            if ($isAccepted && !empty($form->referralDepts)) {
                $this->ensureReferralReviews($suggestion, $form);
            }
        });

        $suggestion->load('reviews')->syncStage();

        StateService::flush();
    }

    private function ensureReferralReviews(Suggestion $suggestion, DecisionForm $form): void
    {
        $existingDeptIds = Review::where('suggestion_id', $suggestion->id)
            ->whereIn('department_id', $form->referralDepts)
            ->pluck('department_id')
            ->all();

        $now = now();
        $fallbackUserId = Auth::id();
        $rows = [];

        $rankedByDept = User::highestRankingInDepartments($form->referralDepts);

        foreach ($form->referralDepts as $deptId) {
            if (in_array($deptId, $existingDeptIds, true)) {
                continue;
            }

            $rows[] = [
                'suggestion_id' => $suggestion->id,
                'department_id' => $deptId,
                'feedback' => 'neutral',
                'comments' => null,
                'complete' => false,
                'user_id' => $rankedByDept->get($deptId)?->id ?? $fallbackUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) Review::insert($rows);
    }
}
