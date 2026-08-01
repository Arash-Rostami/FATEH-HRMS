<?php

namespace App\Livewire\Dashboard\Suggestion;

use App\Livewire\Dashboard\Suggestion\Actions\SubmitDecisionAction;
use App\Livewire\Dashboard\Suggestion\Actions\SubmitFeedbackAction;
use App\Livewire\Dashboard\Suggestion\Actions\MarkImplementationCompleteAction;
use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use App\Livewire\Dashboard\Suggestion\Forms\FeedbackForm;
use App\Livewire\Dashboard\Suggestion\Presentation\SuggestionPresenter;
use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
use App\Support\SuggestionAccessPolicy;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Detail extends Component
{
    public int $suggestionId;

    public FeedbackForm $feedbackForm;
    public DecisionForm $decisionForm;

    #[Computed]
    public function allDepartments(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    #[Computed]
    public function allDepartmentTooltips(): array
    {
        return Department::getCachedModels()->mapWithKeys(fn($d) => [$d->code => $d->tooltipLabel()])->toArray();
    }

    #[Computed]
    public function canDecide(): bool
    {
        return SuggestionAccessPolicy::canDecide($this->suggestion);
    }

    #[Computed]
    public function canMarkComplete(): bool
    {
        return SuggestionAccessPolicy::canMarkComplete($this->suggestion);
    }

    public function markComplete(MarkImplementationCompleteAction $action): void
    {
        $s = $this->suggestion;
        if (!$s) return;
        $action->execute($s);

        unset($this->suggestion, $this->myReview, $this->canMarkComplete);
        $this->dispatch('completion-marked');
        $this->dispatch('toast', message: 'اقدام به عنوان تکمیل‌شده ثبت شد.', type: 'success');
    }

    #[Computed]
    public function canGiveFeedback(): bool
    {
        return SuggestionAccessPolicy::canGiveFeedback($this->suggestion);
    }

    #[Computed]
    public function myReview(): ?Review
    {
        return $this->suggestion?->reviews
            ->firstWhere('department_id', Auth::user()?->profile?->department?->code);
    }

    public function render()
    {
        return view('livewire.dashboard.suggestion.detail', [
            'p' => new SuggestionPresenter($this->suggestion),
        ]);
    }

    public function submitDecision(SubmitDecisionAction $action): void
    {
        $s = $this->suggestion;
        if (!$s) return;
        $action->execute($this->decisionForm, $s);

        $this->decisionForm->reset();
        unset($this->suggestion, $this->myReview);
        $this->dispatch('decision-made');
        $this->dispatch('toast', message: 'تصمیم ثبت شد.', type: 'success');
    }

    public function submitFeedback(SubmitFeedbackAction $action): void
    {
        $s = $this->suggestion;
        if (!$s) return;
        $action->execute($this->feedbackForm, $s);

        $this->feedbackForm->reset();
        unset($this->suggestion, $this->myReview, $this->canGiveFeedback);
        $this->dispatch('feedback-submitted');
        $this->dispatch('toast', message: 'بازخورد ثبت شد.', type: 'success');
    }

    #[Computed]
    public function suggestion(): ?Suggestion
    {
        return Suggestion::with(['user.profile.department', 'reviews.department', 'reviews.user'])
            ->withCount([
                'reviews as agree_count' => fn($q) => $q->where('feedback', 'agree'),
                'reviews as disagree_count' => fn($q) => $q->where('feedback', 'disagree'),
                'reviews as neutral_count' => fn($q) => $q->whereIn('feedback', ['neutral', 'unknown']),
            ])
            ->find($this->suggestionId);
    }
}
