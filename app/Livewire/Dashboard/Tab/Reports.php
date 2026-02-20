<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Report;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Reports extends Component
{
    public $perPage = 10;
    public $view = 'card';
    public $activeReportId = null;

    public function mount()
    {
        $this->view = session('reports_view_mode', 'card');
    }

    public function loadMore()
    {
        $this->perPage += 10;
        unset($this->reports);
        unset($this->hasMorePages);
    }

    public function toggleView($view)
    {
        if (in_array($view, ['card', 'list'])) {
            $this->view = $view;
            session(['reports_view_mode' => $view]);
        }
    }

    public function download($id)
    {
        $report = Report::findOrFail($id);

        if (!$report->active) {
            abort(403);
        }

        if (Storage::exists($report->file_path)) {
            return Storage::download($report->file_path);
        }

        $localPath = storage_path('app/' . $report->file_path);
        if (file_exists($localPath)) {
            return response()->download($localPath);
        }

        if (file_exists(public_path($report->file_path))) {
             return response()->download(public_path($report->file_path));
        }

        $this->dispatch('toast', message: 'File not found.', type: 'error');
    }

    #[Computed(seconds: 14400, cache: true)]
    public function reports()
    {
        $key = 'dashboard.reports.' . $this->perPage;
        return Cache::remember($key, 14400, function () {
            return Report::active()
                ->with(['department', 'user'])
                ->latest()
                ->take($this->perPage)
                ->get();
        });
    }

    #[Computed(seconds: 14400, cache: true)]
    public function hasMorePages()
    {
        $count = Cache::remember('dashboard.reports.count', 300, function () {
            return Report::active()->count();
        });

        return $count > $this->perPage;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reports.index');
    }
}
