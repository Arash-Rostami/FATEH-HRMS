<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\FAQ;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Faqs extends Component
{
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
        return Department::select(['id', 'code', 'name', 'description'])->get();
    }

    #[Computed]
    public function faqs()
    {
        return FAQ::query()
            ->with(['department', 'user'])
            ->when($this->search, fn($q, $s) => $q->where(fn($sub) => $sub
                ->where('question', 'like', "%{$s}%")
                ->orWhere('answer', 'like', "%{$s}%")
            ))
            ->when($this->selectedCategory, fn($q, $c) => $q->where('category', 'like', "%{$c}%"))
            ->when($this->selectedDepartment, fn($q, $d) => $q->where('department_id', 'like', "%{$d}%"))
            ->latest()
            ->paginate($this->perPage);
    }

    public function filterByCategory(?string $category): void
    {
        $this->selectedCategory = $category === 'all' ? null : $category;
        $this->resetPage();
    }

    public function filterByDepartment(?string $departmentCode): void
    {
        $this->selectedDepartment = $departmentCode;
        $this->resetPage();
    }

    public function loadMore(): void { $this->perPage += 10; }

    public function render()
    {
        return view('livewire.dashboard.tab.faqs.index');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedCategory', 'selectedDepartment']);
        $this->resetPage();
    }

    #[Computed]
    public function totalFaqs(): int
    {
        return FAQ::count();
    }

    public function updatedSearch(): void { $this->resetPage(); }
}
