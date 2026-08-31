<?php

namespace App\Livewire\Dashboard\Analytics;

use App\Livewire\Dashboard\Analytics\Presentation\AnalyticsPresenter;
use App\Services\HrAnalyticsService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Main extends Component
{
    #[Computed(seconds: 300, cache: true)]
    public function chartData(): array
    {
        $service = app(HrAnalyticsService::class);

        return [
            'hr_a' => $service->getHrAData(),
            'hr_b' => $service->getHrBData(),
            'hr_c' => $service->getHrCData(),
            'hr_d' => $service->getHrDData(),
            'hr_e' => $service->getHrEData(),
            'hr_f' => $service->getHrFData(),
            'hr_g' => $service->getHrGData(),
            'hr_h' => $service->getHrHData(),
            'hr_i' => $service->getHrIData(),
            'hr_j' => $service->getHrJData(),
            'hr_k' => $service->getHrKData(),
            'hr_l' => $service->getHrLData(),
            'hr_m' => $service->getHrMData(),
            'hr_n' => $service->getHrNData(),
            'hr_o' => $service->getHrOData(),
            'hr_p' => $service->getHrPData(),
            'hr_q' => $service->getHrQData(),
        ];
    }

    #[Computed(seconds: 300, cache: true)]
    public function chartFreshness(): string
    {
        return now()->toIso8601String();
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.analytics.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }

    public function render(): View
    {
        $age = (int) round(max(0, now()->diffInMinutes(Carbon::parse($this->chartFreshness))));

        return view('livewire.dashboard.analytics', [
            'chartData' => $this->chartData,
            'presenter' => new AnalyticsPresenter(),
            'snapshotAge' => $age,
        ])->extends('layouts.app')->section('content');
    }
}
