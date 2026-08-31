<?php

namespace App\Livewire\Dashboard\Suggestion\Actions;

use App\Livewire\Dashboard\Suggestion\Forms\SuggestionForm;
use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
use App\Services\Menu\StateService;
use App\Support\SuggestionAccessPolicy;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSuggestionAction
{
    use CleansAttachedFiles, StoresAttachedFiles;

    public function execute(SuggestionForm $form): void
    {
        $form->validate();

        if (in_array(Auth::user()?->profile?->department_id, ['MA', 'MG'], true)) {
            throw ValidationException::withMessages([
                'form.title' => __('resources/suggestion/strings.errors.ma_restricted'),
            ]);
        }

        $validDeptCodes = Department::getCachedModels()->keys()->all();
        foreach ($form->departments as $dept) {
            if (!in_array($dept, $validDeptCodes, true)) {
                throw ValidationException::withMessages(['departments' => 'واحد انتخابی معتبر نیست.']);
            }
        }

        DB::transaction(function () use ($form) {
            $authDeptCode = Auth::user()->profile?->department_id;
            $authIsManager = Auth::user()?->isDeptHead() ?? false;

            $suggestion = Suggestion::create([
                'title'       => $form->title,
                'description' => $form->descriptionSelf,
                'departments' => $this->mergeDepartments($form, $authDeptCode),
                'purpose'     => $form->purpose,
                'rule'        => $form->rule,
                'attachment'  => $form->attachment
                    ? static::storeAttachment(
                        $form->attachment,
                        'suggestions',
                        fn ($f) => time() . '_' . Str::random(10) . '.' . $f->extension()
                    )['path']
                    : null,
                'self_fill'   => $form->selfFill,
                'priority'    => $form->priority,
                'stage'       => 'pending',
                'user_id'     => Auth::id(),
            ]);

            if ($rows = $this->buildReviewRows($suggestion, $form, $authDeptCode, $authIsManager)) {
                Review::insert($rows);
            }

            $suggestion->load('reviews')->syncStage();
        });

        StateService::flush();
    }

    private function mergeDepartments(SuggestionForm $form, ?string $authDeptCode): array
    {
        return collect($form->departments)
            ->filter(fn($dept) => $dept !== 'MA')
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
        return SuggestionAccessPolicy::buildReviewRows(
            suggestionId: $suggestion->id,
            departments: $suggestion->departments,
            homeDept: $authDeptCode,
            submitterUserId: Auth::id(),
            submitterIsManager: $authIsManager,
            selfFill: $form->selfFill,
            homeFeedback: $form->feedbackTeam,
            homeComment: $form->descriptionTeam,
            deptFeedback: $form->feedback,
            deptComments: $form->descriptionDepts,
        );
    }
}
