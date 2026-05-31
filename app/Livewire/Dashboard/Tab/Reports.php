<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Report;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Reports extends Component
{
    use FocusOnRecord;

    public int $perPage = 10;
    public string $view = 'card';
    public bool $showModal = false;
    public ?int $activeReportId = null;

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

        $this->dispatch('toast', message: 'فایل موجود نیست!', type: 'error');
    }

    public function focusRecord(int $id): void
    {
        $this->selectPost($id);
    }

    #[Computed(seconds: 14400, cache: true)]
    public function hasMorePages()
    {
        return Report::active()->count() > $this->perPage;
    }

    public function loadMore()
    {
        $this->perPage += 10;
        unset($this->reports);
        unset($this->hasMorePages);
    }

    public function mount()
    {
        $this->view = session('reports_view_mode', 'card');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reports');
    }

    #[Computed(seconds: 14400, cache: true)]
    public function reports()
    {
        return Report::active()
            ->with(['department', 'user'])
            ->latest()
            ->take($this->perPage)
            ->get();
    }

    public function toggleView($view)
    {
        if (in_array($view, ['card', 'list'])) {
            $this->view = $view;
            session(['reports_view_mode' => $view]);
        }
    }

    #[Computed]
    public function totalReports()
    {
        return Report::active()->count();
    }
}
