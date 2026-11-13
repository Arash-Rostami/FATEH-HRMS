<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Models\Review;
use App\Models\Suggestion;
use App\Services\Menu\StateService;
use App\Support\SuggestionAccessPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarkImplementationCompleteAction
{
    public function execute(Suggestion $suggestion): ?Review
    {
        abort_unless(SuggestionAccessPolicy::canMarkComplete($suggestion), 403);

        $deptId = Auth::user()->profile->department_id;
        $review = SuggestionAccessPolicy::departmentReview($suggestion, $deptId);

        DB::transaction(function () use ($review, $suggestion): void {
            $review->complete = true;
            $review->save();

            $suggestion->load('reviews')->syncStage();
        });

        StateService::flush();

        return $review->refresh();
    }
}
