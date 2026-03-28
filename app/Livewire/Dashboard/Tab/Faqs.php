<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\FAQ;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
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
            ->when($this->search, fn($query, $search) => $query->where(fn($q) => $q
                ->where('question', 'like', '%' . $search . '%')
                ->orWhere('answer', 'like', '%' . $search . '%')
            ))
            ->when($this->selectedCategory, fn($query, $category) => $query->where('category', $category))
            ->when($this->selectedDepartment, fn($query, $department) => $query->where('department_id', $department))
            ->latest()
            ->paginate($this->perPage);
    }

    public function filterByCategory(?string $category)
    {
        $this->selectedCategory = $category === 'all' ? null : $category;
        $this->resetPage();
    }

    public function filterByDepartment(?string $departmentCode)
    {
        $this->selectedDepartment = $departmentCode;
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    #[Computed]
    public function totalFaqs()
    {
        return FAQ::count();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.faqs.index');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedCategory = null;
        $this->selectedDepartment = null;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}
