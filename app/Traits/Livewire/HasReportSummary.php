<?php

namespace App\Traits\Livewire;

use App\Services\ProjectTask\ReportingService;
use Livewire\Attributes\Computed;

trait HasReportSummary
{
    #[Computed]
    public function reportSummary(): array
    {
        return $this->activeProjectId
            ? app(ReportingService::class)->summary($this->activeProjectId, (int) auth()->id())
            : ['total' => 0, 'done' => 0, 'percent' => 0.0];
    }
}
