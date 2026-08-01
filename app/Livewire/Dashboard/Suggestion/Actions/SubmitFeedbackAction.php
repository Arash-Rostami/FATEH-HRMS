<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Livewire\Dashboard\Suggestion\Forms\FeedbackForm;
use App\Models\Review;
use App\Models\Suggestion;
use App\Services\Menu\StateService;
use App\Support\SuggestionAccessPolicy;
use Illuminate\Support\Facades\Auth;

class SubmitFeedbackAction
{
    public function execute(FeedbackForm $form, Suggestion $suggestion): void
    {
        abort_unless(SuggestionAccessPolicy::canGiveFeedback($suggestion), 403);

        $form->validate();

        Review::updateOrCreate(
            [
                'suggestion_id' => $suggestion->id,
                'department_id' => Auth::user()?->profile->department_id,
            ],
            [
                'feedback' => $form->feedback,
                'comments' => $form->comment,
                'user_id'  => Auth::id(),
                'complete' => false,
            ]
        );

        $suggestion->load('reviews')->syncStage();

        StateService::flush();
    }
}
