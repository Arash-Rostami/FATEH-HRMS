<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\DeleteEventAction;
use App\Livewire\Dashboard\Tab\Actions\MoveEventAction;
use App\Livewire\Dashboard\Tab\Actions\ResizeEventAction;
use App\Livewire\Dashboard\Tab\Actions\SaveEventAction;
use App\Livewire\Dashboard\Tab\Actions\ShareEventAction;
use App\Livewire\Dashboard\Tab\Forms\EventForm;
use App\Livewire\Dashboard\Tab\Presentation\CalendarPresenter;
use App\Models\Event;
use App\Models\User;
use App\Services\Menu\StateService;
use App\Services\Reservation\EventSyncService;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Throwable;

#[Isolate]
#[Lazy]
class Calendar extends Component
{
    use FocusOnRecord;

    public EventForm $form;
    public string $navigationDate;
    #[Locked]
    public string $view = 'month';
    public string $miniMonthDate;
    public bool $hideFriday = false;
    public string $selectedDate;
    public bool $isCreateModalOpen = false;
    public ?int $deletingEventId = null;

    public bool $isShareModalOpen = false;
    public ?int $sharingEventId = null;
    public array $shareRecipientIds = [];

    private ?CalendarPresenter $presenter = null;

    #[Computed]
    public function activeDate(): array
    {
        return $this->presenter()->activeDate($this->selectedDate);
    }

    #[Computed]
    public function calendarDays(): array
    {
        return $this->presenter()->monthDays($this->navigationDate, $this->selectedDate);
    }

    #[Computed]
    public function currentMonthName(): string
    {
        return $this->presenter()->currentMonthName($this->navigationDate);
    }

    #[Computed]
    public function rangeLabel(): string
    {
        return $this->presenter()->rangeLabel($this->navigationDate, $this->view);
    }

    #[Computed]
    public function miniMonthDays(): array
    {
        return $this->presenter()->monthDays($this->miniMonthDate, $this->selectedDate);
    }

    #[Computed]
    public function rangeEvents(): array
    {
        return $this->presenter()->rangeEvents($this->navigationDate, $this->view);
    }

    #[Computed]
    public function weekLabels(): array
    {
        return $this->presenter()->weekLabels();
    }

    public function gridData(string $scope): array
    {
        return $this->presenter()->gridData($this->navigationDate, $scope, $this->rangeEvents);
    }

    public function agendaItems(string $scope): array
    {
        return $this->presenter()->agendaItems($this->navigationDate, $scope, $this->rangeEvents);
    }

    #[Computed]
    public function selectedDayEvents(): Collection
    {
        return $this->presenter()->selectedDayEvents($this->selectedDate);
    }

    public function confirmDelete(int $eventId): void
    {
        $this->deletingEventId = $eventId;

        $this->dispatch('open-confirmation', [
            'title' => 'حذف رویداد؟',
            'message' => 'آیا مطمئن هستید که می‌خواهید این رویداد را حذف کنید؟ این عملیات غیرقابل بازگشت است.',
            'method' => 'deleteEvent',
            'params' => $eventId,
            'type' => 'non-livewire'
        ]);
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::where('user_id', Auth::id())->find($eventId);

        if ($event && EventSyncService::isReservationEvent($event->description)) {
            $this->deletingEventId = null;
            $this->dispatch('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای لغو آن به تب رزرو مراجعه کنید.', type: 'error');
            return;
        }

        app(DeleteEventAction::class)->execute($eventId, Auth::id());
        $this->deletingEventId = null;
    }

    public function editEvent(int $eventId): void
    {
        $event = Event::where('user_id', Auth::id())->find($eventId);

        if (!$event) {
            return;
        }

        if (EventSyncService::isReservationEvent($event->description)) {
            $this->dispatch('toast', message: 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.', type: 'error');
            return;
        }

        $this->form->editingId = $eventId;
        $this->form->title = $event->title;
        $this->form->description = $event->description ?? '';
        $j = Jalalian::fromCarbon($event->date);
        $this->form->dateYear = $j->getYear();
        $this->form->dateMonth = $j->getMonth();
        $this->form->dateDay = $j->getDay();
        $this->form->time = $event->date->format('H:i');
        $this->form->private = (bool)$event->private;
        $this->form->remindHours = $event->remind_hours;
        $this->form->durationMinutes = $event->duration_minutes ?: Event::DEFAULT_DURATION_MINUTES;

        $this->isCreateModalOpen = true;
    }

    public function reservationHint(): void
    {
        $this->dispatch('toast', message: 'این رزرو از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر یا لغو آن به تب «رزرو» مراجعه کنید.', type: 'error');
    }

    public function focusRecord(int $id): void
    {
        $event = Event::find($id);

        if ($event) {
            $jalali = Jalalian::fromCarbon($event->date);
            $this->navigationDate = $jalali->format('Y-m-d');
            $this->selectedDate = $jalali->format('Y-m-d');
            $this->invalidateNavigationComputeds();
        }
    }

    public function goToToday(): void
    {
        $now = Jalalian::now();
        $today = $now->format('Y-m-d');
        $this->navigationDate = $today;
        $this->selectedDate = $today;
        $this->miniMonthDate = $today;
        $this->invalidateNavigationComputeds();
    }

    public function goToDate(string $jalaliYmd): void
    {
        $this->selectedDate = $jalaliYmd;
        $this->navigationDate = $jalaliYmd;
        $this->miniMonthDate = $jalaliYmd;
        $this->invalidateNavigationComputeds();
        unset($this->miniMonthDays);
    }

    public function stepMiniMonth(int $deltaMonths): void
    {
        try {
            $j = Jalalian::fromFormat('Y-m-d', $this->miniMonthDate);
            $this->miniMonthDate = $deltaMonths >= 0
                ? $j->addMonths($deltaMonths)->format('Y-m-d')
                : $j->subMonths(abs($deltaMonths))->format('Y-m-d');
        } catch (Throwable $e) {
            return;
        }
        unset($this->miniMonthDays);
    }

    public function mount(): void
    {
        StateService::markViewed('calendar');

        $now = Jalalian::now();
        $today = $now->format('Y-m-d');

        if (!isset($this->navigationDate)) {
            $this->navigationDate = $today;
        }

        if (!isset($this->miniMonthDate)) {
            $this->miniMonthDate = $today;
        }

        if (!isset($this->selectedDate)) {
            $this->selectedDate = $today;
        }

        $persisted = session('calendar_view_mode');
        if (in_array($persisted, ['month', 'week', 'day'], true)) {
            $this->view = $persisted;
        }
    }

    public function nextPeriod(): void
    {
        $j = Jalalian::fromFormat('Y-m-d', $this->navigationDate);

        if ($this->view === 'month') {
            $this->navigationDate = $j->addMonths(1)->format('Y-m-d');
        } else {
            $days = $this->view === 'week' ? 7 : 1;
            $this->navigationDate = Jalalian::fromCarbon($j->toCarbon()->addDays($days))->format('Y-m-d');
        }

        $this->selectedDate = $this->navigationDate;
        $this->invalidateNavigationComputeds();
    }

    public function openCreateModal(): void
    {
        $this->form->resetForm($this->selectedDate);
        $this->isCreateModalOpen = true;
    }

    public function prevPeriod(): void
    {
        $j = Jalalian::fromFormat('Y-m-d', $this->navigationDate);

        if ($this->view === 'month') {
            $this->navigationDate = $j->subMonths(1)->format('Y-m-d');
        } else {
            $days = $this->view === 'week' ? 7 : 1;
            $this->navigationDate = Jalalian::fromCarbon($j->toCarbon()->subDays($days))->format('Y-m-d');
        }

        $this->selectedDate = $this->navigationDate;
        $this->invalidateNavigationComputeds();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.calendar', ['presenter' => $this->presenter()]);
    }

    public function placeholder(): \Illuminate\View\View
    {
        return view('livewire.dashboard.tab.calendar.placeholder');
    }

    public function saveEvent(SaveEventAction $action): void
    {
        try {
            $action->execute($this->form, Auth::id());
        } catch (InvalidArgumentException $e) {
            $this->addError('form.date', 'تاریخ نامعتبر است');
            return;
        }

        $this->isCreateModalOpen = false;
        $this->form->resetForm($this->selectedDate);
    }

    public function selectDate(string $jalaliYmd): void
    {
        $this->selectedDate = $jalaliYmd;
        $this->invalidateNavigationComputeds();
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['month', 'week', 'day'], true)) {
            return;
        }

        $this->view = $view;
        $this->navigationDate = $this->selectedDate;
        session(['calendar_view_mode' => $view]);
        $this->invalidateNavigationComputeds();
    }

    public function moveEvent(int $id, ?string $dateJalali, ?string $timePart, string $clientMtime): array
    {
        $result = app(MoveEventAction::class)->execute($id, $dateJalali, $timePart, $clientMtime);

        return $this->handleGridActionResult(
            $result,
            $id,
            'رویداد جابجا شد.',
            'تاریخ یا زمان نامعتبر است.',
            'جابجایی ناموفق بود.',
            'revert-event-',
        );
    }

    public function resizeEvent(int $id, int $durationMinutes, string $clientMtime): array
    {
        $result = app(ResizeEventAction::class)->execute($id, $durationMinutes, $clientMtime);

        return $this->handleGridActionResult(
            $result,
            $id,
            'مدت رویداد به‌روزرسانی شد.',
            'مدت نامعتبر است.',
            'تغییر مدت ناموفق بود.',
            'revert-resize-',
        );
    }

    private function handleGridActionResult(
        array $result,
        int $id,
        string $successMessage,
        string $invalidInputMessage,
        string $fallbackMessage,
        string $revertEventPrefix,
    ): array {
        if ($result['ok']) {
            $this->dispatch('toast', message: $successMessage, type: 'success');
            return $result;
        }

        $reasonToasts = [
            'not_owner' => 'شما مالک این رویداد نیستید.',
            'locked' => 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.',
            'invalid_input' => $invalidInputMessage,
            'stale' => 'رویداد توسط کاربر دیگری تغییر کرد؛ تغییر شما برگردانده شد.',
            'rate_limited' => 'درخواست بیش از حد؛ کمی صبر کنید.',
        ];

        $reason = $result['reason'] ?? 'invalid_input';
        $this->dispatch('toast', message: $reasonToasts[$reason] ?? $fallbackMessage, type: 'error');

        $this->dispatch($revertEventPrefix . $id, $result['revertTo'] ?? []);

        return $result;
    }

    #[Computed]
    public function availableUsers(): array
    {
        $authId = Auth::id();

        return User::getCachedActiveOptions()
            ->except($authId)
            ->map(fn($name, $id) => ['id' => $id, 'full_name' => $name])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function sharingEvent(): ?Event
    {
        if ($this->sharingEventId === null) {
            return null;
        }

        return Event::query()
            ->where('user_id', Auth::id())
            ->find($this->sharingEventId);
    }

    public function openShareModal(int $eventId): void
    {
        $event = Event::query()
            ->where('user_id', Auth::id())
            ->find($eventId);

        if (!$event) {
            return;
        }

        if (EventSyncService::isReservationEvent($event->description)) {
            $this->dispatch('toast', message: 'این رویداد از طریق سیستم رزرو به‌طور خودکار به‌اشتراک گذاشته شده است.', type: 'error');
            return;
        }

        $this->sharingEventId = $eventId;
        $this->shareRecipientIds = $event->shares()
            ->pluck('user_id')
            ->map(fn($id) => (string)$id)
            ->all();

        $this->isShareModalOpen = true;
    }

    public function shareEvent(ShareEventAction $action): void
    {
        if ($this->sharingEventId === null) {
            return;
        }

        try {
            $summary = $action->execute($this->sharingEventId, Auth::id(), $this->shareRecipientIds);
        } catch (InvalidArgumentException $e) {
            $this->addError('share', $e->getMessage());
            return;
        } catch (Throwable $e) {
            report($e);
            $this->addError('share', 'اشتراک‌گذاری با خطا مواجه شد؛ دوباره تلاش کنید.');
            $this->dispatch('toast', message: 'اشتراک‌گذاری با خطا مواجه شد.', type: 'error');
            return;
        }

        if ($summary['added'] > 0) {
            $this->dispatch(
                'toast',
                message: sprintf('رویداد «%s» با %d نفر به اشتراک گذاشته شد.', $summary['event_title'], $summary['added']),
                type: 'success',
            );
        } elseif ($summary['removed'] > 0) {
            $this->dispatch(
                'toast',
                message: sprintf('اشتراک رویداد برای %d نفر لغو شد.', $summary['removed']),
                type: 'success',
            );
        } else {
            $this->dispatch('toast', message: 'تغییری در اشتراک‌گذاری اعمال نشد.', type: 'info');
        }

        $this->isShareModalOpen = false;
        $this->sharingEventId = null;
        $this->shareRecipientIds = [];
    }

    private function presenter(): CalendarPresenter
    {
        return $this->presenter ??= new CalendarPresenter();
    }

    private function invalidateNavigationComputeds(): void
    {
        unset($this->rangeEvents, $this->calendarDays, $this->rangeLabel, $this->currentMonthName, $this->activeDate, $this->selectedDayEvents);
    }
}
