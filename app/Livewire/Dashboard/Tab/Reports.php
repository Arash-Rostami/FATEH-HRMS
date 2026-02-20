<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Report;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function loadMore()
    {
        $this->perPage += 10;
    }

    public function getReportsProperty()
    {
        return Report::active()
            ->with(['department', 'user'])
            ->latest()
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reports.index', [
            'reports' => $this->reports
        ]);
    }
}
