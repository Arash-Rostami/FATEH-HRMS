<?php

namespace App\Livewire\Dashboard\Tasksheet;

use App\Livewire\Dashboard\TaskBoard\Presentation\TaskBoardPresenter;
use App\Livewire\Dashboard\Tasksheet\Actions\ExportTasksheetAction;
use App\Livewire\Dashboard\Tasksheet\Presentation\TasksheetPresenter;
use App\Models\User;
use App\Services\ProjectTask\TasksheetService;
use App\Services\ProjectTask\TasksheetShareService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Lazy]
class Main extends Component
{
    private const MAX_ACTIVITY = 200;

    private const PRESETS = ['this_month', 'last_month', 'this_quarter', 'custom'];

    private const GUARDED_WHEN_READ_ONLY = [
        'preset', 'fromYear', 'fromMonth', 'fromDay', 'toYear', 'toMonth', 'toDay', 'scopeProjectId', 'viewingBaseline',
        'shareTarget', 'shareRecipientId',
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

    #[Locked]
    public ?array $goodRange = null;

    public bool $viewingBaseline = false;

    public bool $activityOpen = false;

    #[Locked]
    public int $activityLimit = 50;

    public ?int $shareRecipientId = null;

    public string $shareTarget = 'boss';

    public function updating(string $name, mixed $value): void
    {
        abort_if($this->readOnly && in_array($name, self::GUARDED_WHEN_READ_ONLY, true), 403);
    }

    public function mount(?int $subject = null): void
    {
        $viewer = auth()->user();
        $privileged = $viewer->isAdmin() || $viewer->isDeveloper();
        $this->readOnly = $subject !== null;
        $this->subjectUserId = $subject ?? (int) request()->query('user', $viewer->id);

        abort_unless(User::whereKey($this->subjectUserId)->when(!$privileged, fn($q) => $q->active())->exists(), 404);

        if (!$this->readOnly) {
            if ($this->subjectUserId !== $viewer->id) {
                abort_unless($privileged || TasksheetShareService::managersFor($this->subject)->contains('id', $viewer->id), 403);
            }

            $this->shareRecipientId = TasksheetShareService::managerFor($this->subject)?->id;
        }

        $this->rememberRange();
    }

    #[Computed]
    public function subject(): User
    {
        return $this->subjectUserId === (int) auth()->id()
            ? auth()->user()->loadMissing('profile')
            : User::with('profile')->findOrFail($this->subjectUserId);
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

        return TasksheetService::baselineWindow($window['start'], $window['end']);
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

        return app(TasksheetService::class)->report($this->subject, $window['start'], $window['end'], auth()->user());
    }

    #[Computed]
    public function activityFeed(): array
    {
        $window = $this->window;

        $feed = app(TasksheetService::class)->activityFeed($this->subject, $window['start'], $window['end'], 1, $this->activityLimit, auth()->user());
        $feed['has_more'] = $feed['has_more'] && $this->activityLimit < self::MAX_ACTIVITY;

        return $feed;
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

        $capped = $this->capRange($start, $end);

        if (!$capped->equalTo($end)) {
            $end = $capped;
            $endJalali = Jalalian::fromCarbon($end);
            $this->toYear = $endJalali->getYear();
            $this->toMonth = $endJalali->getMonth();
            $this->toDay = $endJalali->getDay();
            $this->dispatch('toast', message: 'بازهٔ انتخابی حداکثر یک سال است؛ تاریخ پایان اصلاح شد.', type: 'warning');
        }

        $this->preset = 'custom';
        $this->goodRange = [$start->toIso8601String(), $end->toIso8601String()];
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
        $this->activityLimit = min(self::MAX_ACTIVITY, $this->activityLimit + 50);
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

        if ($this->shareTarget === 'boss') {
            $recipient = TasksheetShareService::managerFor($this->subject);

            if (!$recipient) {
                $this->dispatch('toast', message: 'مدیری برای این کاربر شناسایی نشد؛ «کاربر دیگر» را انتخاب کنید.', type: 'warning');
                return;
            }
        } else {
            $recipient = User::visibleOnBoard()->find($this->shareRecipientId);

            if (!$recipient || $recipient->is($this->subject)) {
                $this->dispatch('toast', message: 'یک گیرنده انتخاب کنید.', type: 'warning');
                return;
            }
        }

        $result = app(TasksheetShareService::class)->shareWithManager($this->subject, $recipient, windowParams: $this->windowParams());

        $this->dispatch('toast', message: $result['message'], type: $result['success'] ? 'success' : 'warning');
    }

    public function export(ExportTasksheetAction $action): ?StreamedResponse
    {
        if ($this->readOnly) {
            return null;
        }

        $window = $this->window;

        return $action->execute($this->subject, $window['start'], $window['end'], auth()->user());
    }

    private function customWindow(): array
    {
        $parsed = $this->parseCustomRange();

        if ($parsed === null) {
            return $this->goodRange
                ? ['start' => Carbon::parse($this->goodRange[0]), 'end' => Carbon::parse($this->goodRange[1])]
                : $this->jalaliMonthWindow(Jalalian::now());
        }

        [$start, $end] = $parsed;

        return ['start' => $start, 'end' => $this->capRange($start, $end)];
    }

    #[Computed]
    public function rangeInvalid(): bool
    {
        return $this->preset === 'custom' && $this->parseCustomRange() === null;
    }

    private function capRange(Carbon $start, Carbon $end): Carbon
    {
        return $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1 > 365
            ? $start->copy()->addDays(364)->endOfDay()
            : $end;
    }

    private function rememberRange(): void
    {
        if ($this->preset === 'custom' && ($parsed = $this->parseCustomRange())) {
            $this->goodRange = [$parsed[0]->toIso8601String(), $this->capRange(...$parsed)->toIso8601String()];
        }
    }

    private function windowParams(): array
    {
        $params = ['preset' => $this->preset];

        if ($this->preset === 'custom') {
            $params += array_filter([
                'fy' => $this->fromYear,
                'fm' => $this->fromMonth,
                'fd' => $this->fromDay,
                'ty' => $this->toYear,
                'tm' => $this->toMonth,
                'td' => $this->toDay,
            ], fn ($value) => $value !== null);
        }

        if ($this->scopeProjectId !== null) {
            $params['project'] = $this->scopeProjectId;
        }

        return $params;
    }

    private function parseCustomRange(): ?array
    {
        return jalaliSpanFromParts($this->fromYear, $this->fromMonth, $this->fromDay, $this->toYear, $this->toMonth, $this->toDay);
    }

    public function render(): View
    {
        return view('livewire.dashboard.tasksheet', [
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
