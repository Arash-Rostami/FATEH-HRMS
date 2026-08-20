<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\Report;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Reports extends Component
{
    use FocusOnRecord;

    public int $perPage = 5;
    public string $view = 'card';
    public string $search = '';
    public string $activeFilter = 'all';
    public bool $showModal = false;
    public ?int $activeReportId = null;

    private ?Collection $reportsPage = null;

    public function download($id)
    {
        $report = $this->visibleReportsQuery()->whereKey($id)->first();

        if (!$report) {
            abort(403);
        }

        $disk = Storage::disk('public');

        if ($disk->exists($report->file_path)) {
            return $disk->download($report->file_path);
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
        if ($this->visibleReportsQuery()->whereKey($id)->exists()) {
            $this->activeReportId = $id;
            $this->showModal = true;
        }
    }

    public function loadMore()
    {
        $this->perPage += 10;
        $this->reportsPage = null;
        unset($this->reports, $this->hasMorePages);
    }

    public function mount()
    {
        $this->view = session('reports_view_mode', 'card');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.reports');
    }

    #[Computed]
    public function reports()
    {
        return $this->loadReportsPage()->take($this->perPage)->values();
    }

    #[Computed]
    public function hasMorePages()
    {
        return $this->loadReportsPage()->count() > $this->perPage;
    }

    private function loadReportsPage(): Collection
    {
        return $this->reportsPage ??= $this->scopedReportsQuery()
            ->with(['department', 'user'])
            ->orderByDesc('pinned')
            ->latest()
            ->take($this->perPage + 1)
            ->get();
    }

    protected function scopedReportsQuery()
    {
        return $this->visibleReportsQuery()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%")))
            ->when($this->activeFilter !== 'all', fn ($q) => $q->where('department_id', $this->activeFilter));
    }

    protected function visibleReportsQuery()
    {
        $dept = Auth::user()?->profile?->department_id;

        return Report::active()
            ->notExpired()
            ->visibleTo($dept);
    }

    #[Computed]
    public function departments()
    {
        $codes = $this->visibleReportsQuery()->whereNotNull('department_id')->pluck('department_id');

        return Department::getCachedModels()
            ->filter(fn ($model, $code) => $codes->contains($code))
            ->values();
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
        return $this->visibleReportsQuery()->count();
    }
}
