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
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Lazy]
class Main extends Component
{
    use FocusOnRecord;
    use WithFileUploads;
    use WithPagination;

    public SuggestionForm $form;
    public int $perPage = 5;
    public string $panel = 'empty';
    public string $search = '';
    public bool $attentionOnly = false;
    public ?int $selectedId = null;
    public bool $showWorkflowModal = false;

    #[Computed]
    public function authDeptCode(): ?string
    {
        return Auth::user()->profile?->department_id;
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

    public function focusRecord(int $id): bool
    {
        if (Suggestion::whereKey($id)->exists()) {
            $this->selectSuggestion($id);
            return true;
        }

        return false;
    }

    #[On(['decision-made', 'feedback-submitted', 'completion-marked'])]
    public function refreshSuggestions(): void
    {
        unset($this->suggestions, $this->topContributors);
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
    {
        return new SuggestionPresenter($suggestion);
    }

    public function placeholder(): \Illuminate\View\View
    {
        return view('livewire.dashboard.suggestion.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    public function render()
    {
        return view('livewire.dashboard.suggestion', [
            'rules' => Suggestion::RULES,
            'purposes' => Suggestion::PURPOSES,
            'priorities' => Suggestion::PRIORITIES,
            'reviewFeedback' => Review::FEEDBACKS,
            'workFlow' => SuggestionPresenter::workflowSteps()
        ])->extends('layouts.app')->section('content');
    }

    #[Computed]
    public function topContributors()
    {
        return Suggestion::query()
            ->select('user_id', \DB::raw('count(*) as total_suggestions'), \DB::raw("SUM(stage IN ('accepted', 'closed')) as accepted_suggestions"))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('accepted_suggestions')
            ->orderByDesc('total_suggestions')
            ->limit(3)
            ->get();
    }

    public function selectSuggestion(int $id): void
    {
        $this->selectedId = $id;
        $this->panel = 'detail';
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
            ->with(['user.profile.department', 'reviews'])
            ->selectRaw('*, JSON_LENGTH(departments) as departments_count')
            ->withReviewCounts()
            ->when($this->attentionOnly, fn($q) => $q->attentionRequired())
            ->when($this->search !== '', function ($q) {
                $term = '%' . addcslashes($this->search, '\\%_') . '%';

                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', $term);

                    preg_match('/^SN-(\d{2,8})(?:-(\d{1,6}))?$/i', $this->search, $m);
                    $digits = $m[1] ?? '';
                    $idPart = $m[2] ?? '';

                    if ($digits !== '') {
                        $dateLike = strlen($digits) > 4 ? substr($digits, 0, 4) . '-' . substr($digits, 4) : $digits;
                        if (strlen($digits) > 6) {
                            $dateLike = substr($dateLike, 0, 7) . '-' . substr($digits, 6);
                        }

                        $w->orWhere(function ($r) use ($dateLike, $idPart) {
                            $r->whereRaw('created_at like ?', [$dateLike . '%']);
                            if ($idPart !== '') {
                                $r->whereRaw("LPAD(id, 6, '0') like ?", [str_pad($idPart, 6, '0', STR_PAD_LEFT) . '%']);
                            }
                        });
                    } else {
                        $w->orWhereRaw(
                            "CONCAT('SN-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 6, '0')) like ?",
                            [$term]
                        );
                    }
                });
            })
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setAttentionOnly(bool $value): void
    {
        $this->attentionOnly = $value;
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
