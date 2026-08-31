<?php

namespace App\Livewire\Dashboard\Tasksheet;

use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Livewire\Dashboard\Tasksheet\Actions\ExportTasksheetAction;
use App\Livewire\Dashboard\Tasksheet\Presentation\TasksheetPresenter;
use App\Models\User;
use App\Services\ProjectTask\TasksheetService;
use App\Services\ProjectTask\TasksheetShareService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Lazy]
class Main extends Component
{
    private const PRESETS = ['this_month', 'last_month', 'this_quarter', 'custom'];

    private const GUARDED_WHEN_READ_ONLY = [
        'preset', 'fromYear', 'fromMonth', 'fromDay', 'toYear', 'toMonth', 'toDay', 'scopeProjectId', 'viewingBaseline',
    ];

    #[Locked]
    public int $subjectUserId;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    #[Url(as: 'preset')]
    public string $preset = 'this_month';

    #[Url(as: 'fy')]
    public ?int $fromYear = null;

    #[Url(as: 'fm')]
    public ?int $fromMonth = null;

    #[Url(as: 'fd')]
    public ?int $fromDay = null;

    #[Url(as: 'ty')]
    public ?int $toYear = null;

    #[Url(as: 'tm')]
    public ?int $toMonth = null;

    #[Url(as: 'td')]
    public ?int $toDay = null;

    #[Locked]
    #[Url(as: 'project')]
    public ?int $scopeProjectId = null;

    public bool $viewingBaseline = false;

    public bool $activityOpen = false;

    public int $activityLimit = 50;

    public ?int $shareRecipientId = null;

    public function updating(string $name, mixed $value): void
    {
        abort_if($this->readOnly && in_array($name, self::GUARDED_WHEN_READ_ONLY, true), 403);
    }

    public function mount(?int $subject = null): void
    {
        $this->readOnly = $subject !== null;
        $this->subjectUserId = $subject ?? (int) request()->query('user', auth()->id());

        abort_unless(User::whereKey($this->subjectUserId)->active()->exists(), 404);

        if (!$this->readOnly) {
            $viewer = auth()->user();
            $deptCode = $this->subject->profile?->department_id;
            $manager = $deptCode ? User::highestRankingInDepartment($deptCode) : null;

            if ($this->subjectUserId !== $viewer->id) {
                abort_unless($viewer->isAdmin() || $viewer->isDeveloper() || $manager?->is($viewer), 403);
            }

            $this->shareRecipientId = $manager?->id;
        }
    }

    #[Computed]
    public function subject(): User
    {
        return User::with('profile')->findOrFail($this->subjectUserId);
    }

    #[Computed]
    public function window(): array
    {
        $window = match ($this->preset) {
            'last_month' => $this->jalaliMonthWindow(Jalalian::now()->subMonths(1)),
            'this_quarter' => $this->jalaliQuarterWindow(Jalalian::now()),
            'custom' => $this->customWindow(),
            default => $this->jalaliMonthWindow(Jalalian::now()),
        };

        if (!$this->viewingBaseline) {
            return $window;
        }

        return [
            'start' => $window['start']->copy()->sub($window['start']->diffAsCarbonInterval($window['end'])),
            'end' => $window['start']->copy()->subSecond(),
        ];
    }

    private function jalaliMonthWindow(Jalalian $month): array
    {
        return [
            'start' => (new Jalalian($month->getYear(), $month->getMonth(), 1))->toCarbon()->startOfDay(),
            'end' => (new Jalalian($month->getYear(), $month->getMonth(), $month->getMonthDays()))->toCarbon()->endOfDay(),
        ];
    }

    private function jalaliQuarterWindow(Jalalian $day): array
    {
        $startMonth = intdiv($day->getMonth() - 1, 3) * 3 + 1;
        $endMonth = new Jalalian($day->getYear(), $startMonth + 2, 1);

        return [
            'start' => (new Jalalian($day->getYear(), $startMonth, 1))->toCarbon()->startOfDay(),
            'end' => (new Jalalian($endMonth->getYear(), $endMonth->getMonth(), $endMonth->getMonthDays()))->toCarbon()->endOfDay(),
        ];
    }

    #[Computed]
    public function report(): array
    {
        $window = $this->window;

        return app(TasksheetService::class)->report($this->subject, $window['start'], $window['end']);
    }

    #[Computed]
    public function activityFeed(): array
    {
        $window = $this->window;

        return app(TasksheetService::class)->activityFeed($this->subject, $window['start'], $window['end'], 1, $this->activityLimit);
    }

    public function setPreset(string $preset): void
    {
        if ($this->readOnly) {
            return;
        }

        if (!in_array($preset, self::PRESETS, true)) {
            return;
        }

        $this->preset = $preset;
        $this->viewingBaseline = false;
        unset($this->window, $this->report, $this->activityFeed);
    }

    public function setCustomRange(): void
    {
        if ($this->readOnly) {
            return;
        }

        $parsed = $this->parseCustomRange();

        if ($parsed === null) {
            $this->dispatch('toast', message: 'بازهٔ انتخاب‌شده نامعتبر است.', type: 'warning');
            return;
        }

        [$start, $end] = $parsed;

        if ($start->diffInDays($end) > 365) {
            $end = $start->copy()->addYear()->endOfDay();
            $endJalali = Jalalian::fromCarbon($end);
            $this->toYear = $endJalali->getYear();
            $this->toMonth = $endJalali->getMonth();
            $this->toDay = $endJalali->getDay();
            $this->dispatch('toast', message: 'بازهٔ انتخابی حداکثر یک سال است؛ تاریخ پایان اصلاح شد.', type: 'warning');
        }

        $this->preset = 'custom';
        $this->viewingBaseline = false;
        unset($this->window, $this->report, $this->activityFeed);
    }

    public function toggleBaselineWindow(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->viewingBaseline = !$this->viewingBaseline;
        unset($this->window, $this->report, $this->activityFeed);
    }

    public function scopeToProject(int $projectId): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->scopeProjectId = $projectId;
    }

    public function clearScope(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->scopeProjectId = null;
    }

    public function toggleActivity(): void
    {
        $this->activityOpen = !$this->activityOpen;
    }

    public function loadMoreActivity(): void
    {
        $this->activityLimit += 50;
    }

    #[Computed]
    public function shareRecipientOptions(): array
    {
        return User::getCachedActiveOptions()->except($this->subjectUserId)->all();
    }

    public function shareWithManager(): void
    {
        if ($this->readOnly) {
            return;
        }

        $recipient = User::find($this->shareRecipientId);

        if (!$recipient) {
            $this->dispatch('toast', message: 'یک گیرنده انتخاب کنید.', type: 'warning');
            return;
        }

        $result = app(TasksheetShareService::class)->shareWithManager($this->subject, $recipient, windowParams: request()->query());

        $this->dispatch('toast', message: $result['message'], type: $result['success'] ? 'success' : 'warning');
    }

    public function export(ExportTasksheetAction $action): ?StreamedResponse
    {
        if ($this->readOnly) {
            return null;
        }

        $window = $this->window;

        return $action->execute($this->subject, $window['start'], $window['end']);
    }

    private function customWindow(): array
    {
        $parsed = $this->parseCustomRange();

        if ($parsed === null) {
            return $this->jalaliMonthWindow(Jalalian::now());
        }

        [$start, $end] = $parsed;

        if ($start->diffInDays($end) > 365) {
            $end = $start->copy()->addYear();
        }

        return ['start' => $start, 'end' => $end];
    }

    private function parseCustomRange(): ?array
    {
        if (!$this->fromYear || !$this->fromMonth || !$this->fromDay || !$this->toYear || !$this->toMonth || !$this->toDay) {
            return null;
        }

        try {
            if (!CalendarUtils::checkDate((int) $this->fromYear, (int) $this->fromMonth, (int) $this->fromDay, true)
                || !CalendarUtils::checkDate((int) $this->toYear, (int) $this->toMonth, (int) $this->toDay, true)) {
                return null;
            }

            $start = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', sprintf('%s/%02d/%02d 00:00:00', $this->fromYear, $this->fromMonth, $this->fromDay))->startOfDay();
            $end = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', sprintf('%s/%02d/%02d 00:00:00', $this->toYear, $this->toMonth, $this->toDay))->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $end->lt($start) ? null : [$start, $end];
    }

    public function render(): View
    {
        return view('livewire.dashboard.tasksheet.main', [
            'presenter' => new TasksheetPresenter(),
            'taskBoardPresenter' => new TaskBoardPresenter(),
        ])
            ->extends('layouts.app')
            ->section('content');
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.tasksheet.placeholder')
            ->extends('layouts.app')
            ->section('content');
    }
}
