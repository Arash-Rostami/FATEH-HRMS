<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\FAQ;
use App\Models\Department;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Faqs extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $selectedCategory = null;

    #[Url]
    public ?int $selectedDepartment = null;

    public int $perPage = 10;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function filterByCategory(?string $category)
    {
        $this->selectedCategory = $category === 'all' ? null : $category;
        $this->resetPage();
    }

    public function filterByDepartment(?int $departmentId)
    {
        $this->selectedDepartment = $departmentId;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedCategory = null;
        $this->selectedDepartment = null;
        $this->resetPage();
    }

    #[Computed]
    public function faqs()
    {
        return FAQ::query()
            ->with(['department', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('question', 'like', '%' . $this->search . '%')
                        ->orWhere('answer', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedCategory, fn($q) => $q->where('category', $this->selectedCategory))
            ->when($this->selectedDepartment, fn($q) => $q->where('department_id', $this->selectedDepartment))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return FAQ::distinct()->pluck('category')->filter()->values();
    }

    #[Computed]
    public function departments()
    {
        return Department::select('id', 'name', 'description')->get();
    }

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.faqs.index');
    }
}
