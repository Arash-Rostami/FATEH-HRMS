<?php

namespace App\Livewire\Dashboard\Project;

use App\Livewire\Dashboard\Dms\Presentation\DmsPresenter;
use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Services\ProjectTask\ReportingService;
use App\Traits\HasReportSummary;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Defer]
class Report extends Component
{
    use HasReportSummary;

    #[Locked]
    public ?int $activeProjectId = null;

    public string $reportStatusFilter = '';
    public string $reportSearch = '';
    public ?int $reportAssigneeFilter = null;
    public ?string $reportPriorityFilter = null;
    public ?string $reportDepartmentFilter = null;
    public ?string $reportSchemeFilter = null;
    public array $reportSort = ['field' => 'deadline', 'dir' => 'asc'];
    #[Locked]
    public int $reportLimit = 25;

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.report-placeholder');
    }

    #[Computed]
    public function reportData(): array
    {
        if (!$this->activeProjectId) {
            return ['rows' => [], 'hasMore' => false];
        }

        return app(ReportingService::class)->rows(auth()->user(), $this->reportFilters(), $this->reportLimit);
    }

    #[Computed]
    public function reportSchemeProgress(): array
    {
        if (!$this->activeProjectId) {
            return [];
        }

        return app(ReportingService::class)->schemeProgress(auth()->user(), $this->reportFilters());
    }

    #[Computed]
    public function reportFilterOptions(): array
    {
        return $this->activeProjectId
            ? app(ReportingService::class)->filterOptions($this->activeProjectId)
            : ['assignees' => [], 'departments' => [], 'schemes' => []];
    }

    #[Computed]
    public function reportIsFiltered(): bool
    {
        return $this->reportStatusFilter !== ''
            || $this->reportSearch !== ''
            || $this->reportAssigneeFilter !== null
            || $this->reportPriorityFilter !== null
            || $this->reportDepartmentFilter !== null
            || $this->reportSchemeFilter !== null;
    }

    #[Computed]
    public function reportAttachments(): array
    {
        return $this->activeProjectId
            ? app(ReportingService::class)->attachments(auth()->user(), $this->reportFilters())
            : [];
    }

    private function reportFilters(): array
    {
        $filters = ['project_id' => $this->activeProjectId, 'sort' => $this->reportSort];
        if ($this->reportStatusFilter !== '') {
            $filters['status'] = $this->reportStatusFilter;
        }
        if ($this->reportSearch !== '') {
            $filters['search'] = $this->reportSearch;
        }
        if ($this->reportAssigneeFilter !== null) {
            $filters['assignee_id'] = $this->reportAssigneeFilter;
        }
        if ($this->reportPriorityFilter !== null) {
            $filters['priority'] = $this->reportPriorityFilter;
        }
        if ($this->reportDepartmentFilter !== null) {
            $filters['department'] = $this->reportDepartmentFilter;
        }
        if ($this->reportSchemeFilter !== null) {
            $filters['scheme'] = $this->reportSchemeFilter;
        }

        return $filters;
    }

    public function setReportStatusFilter(string $status): void
    {
        $this->reportStatusFilter = $status;
        $this->reportLimit = 25;
        unset($this->reportData, $this->reportSchemeProgress, $this->reportAttachments);
    }

    public function setReportFilter(string $field, $value): void
    {
        $value = $value === '' ? null : $value;
        match ($field) {
            'assignee' => $this->reportAssigneeFilter = $value === null ? null : (int) $value,
            'priority' => $this->reportPriorityFilter = $value === null ? null : (string) $value,
            'department' => $this->reportDepartmentFilter = $value === null ? null : (string) $value,
            'scheme' => $this->reportSchemeFilter = $value === null ? null : (string) $value,
            default => null,
        };
        $this->reportLimit = 25;
        unset($this->reportData, $this->reportSchemeProgress, $this->reportFilterOptions, $this->reportAttachments);
    }

    public function setReportSort(string $field): void
    {
        if (($this->reportSort['field'] ?? null) === $field) {
            $this->reportSort['dir'] = ($this->reportSort['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc';
        } else {
            $this->reportSort = ['field' => $field, 'dir' => 'asc'];
        }
        $this->reportLimit = 25;
        unset($this->reportData);
    }

    public function clearReportFilters(): void
    {
        $this->reportStatusFilter = '';
        $this->reportSearch = '';
        $this->reportAssigneeFilter = null;
        $this->reportPriorityFilter = null;
        $this->reportDepartmentFilter = null;
        $this->reportSchemeFilter = null;
        $this->reportSort = ['field' => 'deadline', 'dir' => 'asc'];
        $this->reportLimit = 25;
        unset($this->reportData, $this->reportSchemeProgress, $this->reportFilterOptions, $this->reportAttachments);
    }

    public function refreshReport(): void
    {
        unset($this->reportData, $this->reportSummary, $this->reportSchemeProgress, $this->reportAttachments);
    }

    public function loadMoreReport(): void
    {
        $this->reportLimit += 25;
        unset($this->reportData);
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.report', [
            'presenter' => new TaskBoardPresenter(),
            'dmsPresenter' => new DmsPresenter(),
            'projectPresenter' => new ProjectPresenter(),
        ]);
    }
}
