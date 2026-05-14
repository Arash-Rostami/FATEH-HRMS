<?php

namespace App\Livewire\Dashboard\Suggestion;

use App\Livewire\Dashboard\Suggestion\Actions\SubmitDecisionAction;
use App\Livewire\Dashboard\Suggestion\Actions\SubmitFeedbackAction;
use App\Livewire\Dashboard\Suggestion\Forms\DecisionForm;
use App\Livewire\Dashboard\Suggestion\Forms\FeedbackForm;
use App\Livewire\Dashboard\Suggestion\Presentation\SuggestionPresenter;
use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
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
    public function canDecide(): bool
    {
        return $this->isCeo() && $this->suggestion?->stage === 'awaiting_decision';
    }

    #[Computed]
    public function canGiveFeedback(): bool
    {
        if (!Auth::user()?->isDeptHead() || Auth::user()?->isCeo()) return false;

        $s = $this->suggestion;
        if (!$s || $s->stage !== 'dept_remarks') return false;
        if (!in_array(Auth::user()->profile?->department_id, $s->departments ?? [])) return false;

        $hasNoReview = !$this->myReview;
        $hasIncompleteReview = $this->myReview && !in_array($this->myReview->feedback, ['agree', 'disagree', 'neutral'], true);

        return $hasNoReview || $hasIncompleteReview;
    }

    #[Computed]
    public function isCeo(): bool
    {
        return Auth::user()?->isCeo() ?? false;
    }

    #[Computed]
    public function isDeptHead(): bool
    {
        return Auth::user()?->isDeptHead() ?? false;
    }

    #[Computed]
    public function myReview(): ?Review
    {
        return $this->suggestion?->reviews
            ->firstWhere('department_id', Auth::user()?->profile?->department->code);
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
        return Suggestion::with(['user.profile', 'reviews.department', 'reviews.user'])
            ->withCount([
                'reviews as agree_count' => fn($q) => $q->where('feedback', 'agree'),
                'reviews as disagree_count' => fn($q) => $q->where('feedback', 'disagree'),
                'reviews as neutral_count' => fn($q) => $q->whereIn('feedback', ['neutral', 'unknown']),
            ])
            ->find($this->suggestionId);
    }
}
