<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\FAQ;
use App\Traits\FocusOnRecord;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Faqs extends Component
{
    use FocusOnRecord;
    use WithPagination;

    public string $search = '';
    public ?string $selectedCategory = null;
    public ?string $selectedDepartment = null;
    public int $perPage = 10;

    #[Computed(seconds: 3600, cache: true, key: 'faq-categories')]
    public function categories()
    {
        return FAQ::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');
    }

    #[Computed(seconds: 3600, cache: true, key: 'faq-departments')]
    public function departments()
    {
        return Department::getCachedOptions()->toArray();
    }

    #[Computed]
    public function faqs()
    {
        return FAQ::query()
            ->with(['department', 'user'])
            ->when(
                $this->open,
                // FOCUS MODE: pin the listing to the single record chosen in the command palette.
                fn($q) => $q->whereKey($this->open),
                // NORMAL MODE: apply the user's own filters.
                fn($q) => $q
                    ->when($this->search, fn($q, $s) => $q->where(fn($sub) => $sub
                        ->where('question', 'like', "%{$s}%")
                        ->orWhere('answer', 'like', "%{$s}%")
                    ))
                    ->when($this->selectedCategory, fn($q, $c) => $q->where('category', 'like', "%{$c}%"))
                    ->when($this->selectedDepartment, fn($q, $d) => $q->where('department_id', 'like', "%{$d}%"))
                    ->latest()
            )
            ->paginate($this->perPage);
    }

    public function filterByCategory(?string $category): void
    {
        $this->open = null; // any deliberate filtering exits focus mode
        $this->selectedCategory = $category === 'all' ? null : $category;
        $this->resetPage();
    }

    public function filterByDepartment(?string $departmentCode): void
    {
        $this->open = null;
        $this->selectedDepartment = $departmentCode;
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function mount(): void
    {
        if ($this->open) {
            $this->perPage = max($this->perPage, 50);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.faqs');
    }

    public function resetFilters(): void
    {
        $this->open = null;
        $this->reset(['search', 'selectedCategory', 'selectedDepartment']);
        $this->resetPage();
    }

    #[Computed]
    public function totalFaqs(): int
    {
        return FAQ::count();
    }

    public function updatedSearch(): void
    {
        $this->open = null;
        $this->resetPage();
    }
}
