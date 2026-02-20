<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Report;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class Reports extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $view = 'card';

    public function mount()
    {
        $this->view = session('reports_view_mode', 'card');
    }

    public function loadMore()
    {
        $this->perPage += 10;
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

        // Fallback check for direct path if Storage facade fails or using local driver differently
        $localPath = storage_path('app/' . $report->file_path);
        if (file_exists($localPath)) {
            return response()->download($localPath);
        }

        // If file is public asset (legacy support)
        if (file_exists(public_path($report->file_path))) {
             return response()->download(public_path($report->file_path));
        }

        session()->flash('error', 'File not found.');
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
