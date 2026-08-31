<?php

namespace App\Livewire\Dashboard\Project;

use App\Livewire\Dashboard\Project\Presentation\ProjectAnalyticsPresenter;
use App\Services\ProjectTask\ReportingService;
use App\Traits\HasReportSummary;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Defer]
class Analytics extends Component
{
    use HasReportSummary;

    #[Locked]
    public ?int $activeProjectId = null;

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.analytics-placeholder');
    }

    #[Computed]
    public function analyticsChartData(): array
    {
        if (!$this->activeProjectId) {
            return [
                'flow' => [],
                'risk' => [],
                'people' => [],
                'meta' => ['total' => 0, 'populated' => ['labels' => false, 'department' => false]],
            ];
        }

        return app(ReportingService::class)->analyticsInsights($this->activeProjectId);
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.analytics', [
            'presenter' => new ProjectAnalyticsPresenter(),
        ]);
    }
}
