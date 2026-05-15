<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\MenuStateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmitDecisionAction
{
    public function execute(DecisionForm $form, Suggestion $suggestion): void
    {
        $form->validate();

        DB::transaction(function () use ($form, $suggestion): void {
            $suggestion->comments = $form->decisionComment;
            $suggestion->save();

            if ($form->decision === 'accepted' && !empty($form->referralDepts)) {
                Review::create([
                    'suggestion_id' => $suggestion->id,
                    'department_id' => 'MA',
                    'feedback' => 'agree',
                    'comments' => $form->decisionComment,
                    'referral' => $form->referralDepts,
                    'actions' => $form->referralActions,
                    'user_id' => Auth::id(),
                    'complete' => false,
                ]);

                $this->ensureReferralReviews($suggestion, $form);
            }
        });

        $suggestion->load('reviews')->syncStage();

        MenuStateService::flush();
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

        foreach ($form->referralDepts as $deptId) {
            if (in_array($deptId, $existingDeptIds, true)) continue;

            $rows[] = [
                'suggestion_id' => $suggestion->id,
                'department_id' => $deptId,
                'feedback' => 'neutral',
                'comments' => null,
                'complete' => false,
                'user_id' => User::highestRankingInDepartment($deptId)?->id ?? $fallbackUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) Review::insert($rows);
    }
}
