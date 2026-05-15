<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Livewire\Dashboard\Suggestion\Forms\SuggestionForm;
use App\Models\Review;
use App\Models\Suggestion;
use App\Services\MenuStateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateSuggestionAction
{
    public function execute(SuggestionForm $form): void
    {
        $form->validate();

        DB::transaction(function () use ($form) {
            $authDeptCode = Auth::user()->profile?->department_id;
            $authIsManager = Auth::user()?->isDeptHead() ?? false;

            $suggestion = Suggestion::create([
                'title'       => $form->title,
                'description' => $form->descriptionSelf,
                'departments' => $this->mergeDepartments($form, $authDeptCode),
                'purpose'     => $form->purpose,
                'rule'        => $form->rule,
                'attachment'  => $form->attachment?->store('suggestions', 'public'),
                'self_fill'   => $form->selfFill,
                'stage'       => 'pending',
                'user_id'     => Auth::id(),
            ]);

            if ($rows = $this->buildReviewRows($suggestion, $form, $authDeptCode, $authIsManager)) {
                Review::insert($rows);
            }

            $suggestion->load('reviews')->syncStage();
        });

        MenuStateService::flush();
    }

    private function mergeDepartments(SuggestionForm $form, ?string $authDeptCode): array
    {
        return collect($form->departments)
            ->when($authDeptCode, fn($c) => $c->prepend($authDeptCode))
            ->unique()
            ->values()
            ->all();
    }

    private function buildReviewRows(
        Suggestion $suggestion,
        SuggestionForm $form,
        ?string $authDeptCode,
        bool $authIsManager
    ): array {
        if (!$form->selfFill && !$authIsManager) {
            return [];
        }

        $uid  = Auth::id();
        $now  = now();
        $managerNote = 'توجه به اینکه پیشنهاد دهنده هستم، نیاز به بررسی و اعلام نظر از سمت مدیریت';

        $base = [
            'suggestion_id' => $suggestion->id,
            'department_id' => $authDeptCode,
            'user_id'       => $uid,
            'complete'      => false,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        if ($authIsManager && !$form->selfFill) {
            return [array_merge($base, [
                'feedback' => 'agree',
                'comments' => $managerNote,
            ])];
        }

        $defaultFeedback = $authIsManager ? 'agree' : 'unknown';
        $defaultComment  = $authIsManager ? $managerNote : null;

        $rows = [array_merge($base, [
            'feedback' => $form->feedbackTeam ?: $defaultFeedback,
            'comments' => $form->descriptionTeam ?: $defaultComment,
        ])];

        foreach ($form->departments as $dept) {
            $rows[] = array_merge($base, [
                'department_id' => $dept,
                'feedback'      => $form->feedback[$dept] ?? 'unknown',
                'comments'      => $form->descriptionDepts[$dept] ?? '',
            ]);
        }

        return $rows;
    }
}
