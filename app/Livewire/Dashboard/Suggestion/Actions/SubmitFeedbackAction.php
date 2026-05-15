<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Models\Review;
use App\Models\Suggestion;
use App\Livewire\Dashboard\Suggestion\Forms\FeedbackForm;
use App\Services\MenuStateService;
use Illuminate\Support\Facades\Auth;

class SubmitFeedbackAction
{
    public function execute(FeedbackForm $form, Suggestion $suggestion): void
    {
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

        MenuStateService::flush();
    }
}
