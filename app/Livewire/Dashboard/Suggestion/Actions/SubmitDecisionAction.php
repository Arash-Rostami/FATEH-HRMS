<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Models\Review;
use App\Models\Suggestion;
use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use Illuminate\Support\Facades\Auth;

class SubmitDecisionAction
{
    public function execute(DecisionForm $form, Suggestion $suggestion): void
    {
        $form->validate();

        $suggestion->comments = $form->decisionComment;
        $suggestion->save();

        if ($form->decision === 'accepted' && !empty($form->referralDepts)) {
            Review::create([
                'suggestion_id' => $suggestion->id,
                'department_id' => 'MA',
                'feedback'      => 'agree',
                'comments'      => $form->decisionComment,
                'referral'      => $form->referralDepts,
                'actions'       => $form->referralActions,
                'user_id'       => Auth::id(),
                'complete'      => false,
            ]);
        }

        $suggestion->load('reviews')->syncStage();
    }
}
