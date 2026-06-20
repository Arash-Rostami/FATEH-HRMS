<?php

namespace App\Livewire\Dashboard\Suggestion;

use App\Livewire\Dashboard\Suggestion\Actions\CreateSuggestionAction;
use App\Livewire\Dashboard\Suggestion\Forms\SuggestionForm;
use App\Livewire\Dashboard\Suggestion\Presentation\SuggestionPresenter;
use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Main extends Component
{
    use FocusOnRecord;
    use WithFileUploads;
    use WithPagination;

    public SuggestionForm $form;
    public int $perPage = 5;
    public string $panel = 'empty';
    public string $search = '';
    public ?int $selectedId = null;
    public bool $showWorkflowModal = false;

    #[Computed]
    public function authDeptCode(): ?string
    {
        return Auth::user()->profile?->department_id;
    }

    #[Computed]
    public function authIsManager(): bool
    {
        return Auth::user()?->isDeptHead() ?? false;
    }

    #[Computed]
    public function availableDepartments(): array
    {
        $exclude = array_filter([$this->authDeptCode, 'MA']);

        return collect($this->departmentNames)
            ->reject(fn($name, $code) => in_array($code, $exclude, true))
            ->all();
    }

    #[Computed(persist: true, seconds: 1)]
    public function departmentNames(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    public function focusRecord(int $id): void
    {
        if (Suggestion::whereKey($id)->exists()) {
            $this->selectSuggestion($id);
        }
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->selectedId = null;
        $this->panel = 'create';
    }

    public function presenter($suggestion)

    #[Computed]
    public function topContributors()
    {
        return Suggestion::query()
            ->select('user_id', \DB::raw('count(*) as total_suggestions'), \DB::raw("SUM(stage = 'accepted') as accepted_suggestions"))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('accepted_suggestions')
            ->orderByDesc('total_suggestions')
            ->limit(3)
            ->get();
    }
    {
        return new SuggestionPresenter($suggestion);
    }

    public function render()
    {
        return view('livewire.dashboard.suggestion', [
            'rules' => Suggestion::RULES,
            'purposes' => Suggestion::PURPOSES,
            'reviewFeedback' => Review::FEEDBACKS,
            'workFlow' => SuggestionPresenter::workflowSteps()
        ])->extends('layouts.app')->section('content');
    }

    public function selectSuggestion(int $id): void
    {
        $this->selectedId = $id;
        $this->panel = 'detail';
    }

    #[Computed]
    public function selected(): ?Suggestion
    {
        return $this->selectedId
            ? Suggestion::with(['user', 'reviews.department'])->find($this->selectedId)
            : null;
    }

    public function submit(CreateSuggestionAction $action): void
    {
        $action->execute($this->form);

        $this->dispatch('toast', message: 'پیشنهاد با موفقیت ثبت شد.', type: 'success');
        $this->resetForm();
        $this->panel = 'empty';
    }

    #[Computed]
    public function suggestions()
    {
        return Suggestion::query()
            ->with(['user', 'reviews'])
            ->selectRaw('*, JSON_LENGTH(departments) as departments_count')
            ->withCount([
                'reviews as agree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['agree']),
                'reviews as neutral_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['neutral']),
                'reviews as disagree_count' => fn($q) => $q->whereRaw('LOWER(feedback) = ?', ['disagree']),
            ])
            ->when($this->search, function ($q) {
                $term = "%{$this->search}%";
                $q->where('title', 'like', $term)
                    ->orWhereRaw(
                        "CONCAT('SN-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 6, '0')) like ?",
                        [$term]
                    );
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function recordFocusType(): string
    {
        return 'suggestion';
    }

    private function resetForm(): void
    {
        $this->form->reset();
        $this->resetValidation();
    }
}
