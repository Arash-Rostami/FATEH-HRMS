<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\DeleteEventAction;
use App\Livewire\Dashboard\Tab\Actions\SaveEventAction;
use App\Livewire\Dashboard\Tab\Actions\ShareEventAction;
use App\Livewire\Dashboard\Tab\Forms\EventForm;
use App\Models\Event;
use App\Models\Profile;
use App\Models\User;
use App\Services\HolidayService;
use App\Services\Menu\StateService;
use App\Services\Reservation\EventSyncService;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Throwable;

class Calendar extends Component
{
    use FocusOnRecord;

    public EventForm $form;
    public int $currentYear;
    public int $currentMonth;
    public string $selectedDate;
    public bool $isCreateModalOpen = false;
    public ?int $deletingEventId = null;

    public bool $isShareModalOpen = false;
    public ?int $sharingEventId = null;
    public array $shareRecipientIds = [];

    #[Computed]
    public function activeDate(): array
    {
        try {
            $jalali = Jalalian::fromFormat('Y-m-d', $this->selectedDate);
        } catch (Throwable $e) {
            $jalali = Jalalian::now();
        }

        return [
            'jalali' => $jalali->format('l، d F Y'),
            'gregorian' => $jalali->toCarbon()->format('D, d M Y'),
            'isToday' => $jalali->format('Y-m-d') === Jalalian::now()->format('Y-m-d'),
        ];
    }

    #[Computed]
    public function calendarDays(): array
    {
        $days = [];

        try {
            $firstDay = new Jalalian($this->currentYear, $this->currentMonth, 1);
            $daysInMonth = $firstDay->getMonthDays();
            $startDayOfWeek = $firstDay->getDayOfWeek();

            $startDate = $firstDay->toCarbon()->startOfDay();
            $endDate = clone $startDate;
            $endDate->addDays($daysInMonth - 1)->endOfDay();
        } catch (Throwable $e) {
            return [];
        }

        $monthEvents = Event::query()
            ->with('shares:user_id,event_id')
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) {
                $authId = Auth::id();
                $q->where('user_id', $authId)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $authId));
            })
            ->get()
            ->groupBy(fn($e) => Jalalian::fromCarbon($e->date)->format('Y-m-d'));

        $profiles = Profile::select('id', 'birthdate', 'start_date')
            ->whereNotNull('birthdate')
            ->orWhereNotNull('start_date')
            ->get();

        $birthdays = $profiles->pluck('birthdate')->filter()->map(fn($d) => $d->format('m-d'))->flip();
        $anniversaries = $profiles->pluck('start_date')->filter()->map(fn($d) => $d->format('m-d'))->flip();

        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        $todayStr = Jalalian::now()->format('Y-m-d');
        $currentDate = clone $startDate;

        $authId = Auth::id();
        $now = now();
        $imminentEnd = (clone $now)->addDay();
        $isShared = fn(Event $e) => $e->user_id === $authId
            ? $e->shares->isNotEmpty()
            : $e->shares->contains('user_id', $authId);

        $currentDate = clone $startDate;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            try {
                $dateString = sprintf('%04d-%02d-%02d', $this->currentYear, $this->currentMonth, $day);
                $mdKey = $currentDate->format('m-d');

                $dayEvents = $monthEvents->get($dateString);
                $hasEvent = $dayEvents !== null;
                $hasBirthday = $birthdays->has($mdKey);
                $hasAnniversary = $anniversaries->has($mdKey);
                $dayHolidays = HolidayService::getHolidaysForDate($dateString);
                $hasHoliday = $dayHolidays !== [];

                $hasShared = $hasEvent && $dayEvents->contains($isShared);
                $hasImminentShared = $hasShared && $dayEvents->contains(
                    fn(Event $e) => $isShared($e) && $e->date >= $now && $e->date <= $imminentEnd
                );

                $eventCount = ($hasEvent ? $dayEvents->count() : 0) +
                    ($hasBirthday ? 1 : 0) +
                    ($hasAnniversary ? 1 : 0);

                $days[] = [
                    'day' => $day,
                    'date' => $dateString,
                    'isToday' => $dateString === $todayStr,
                    'isSelected' => $dateString === $this->selectedDate,
                    'hasEvents' => $hasEvent,
                    'hasBirthday' => $hasBirthday,
                    'hasAnniversary' => $hasAnniversary,
                    'eventCount' => $eventCount,
                    'hasShared' => $hasShared,
                    'hasImminentShared' => $hasImminentShared,
                    'hasHoliday' => $hasHoliday,
                    'holidayTitle' => $hasHoliday ? $dayHolidays[0]['title'] : null,
                ];

                $currentDate->addDay();
            } catch (Throwable $e) {
                $days[] = null;
            }
        }

        return $days;
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

    #[Computed]
    public function currentMonthName(): string
    {
        try {
            return (new Jalalian($this->currentYear, $this->currentMonth, 1))->format('F Y');
        } catch (Throwable $e) {
            return '';
        }
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

        $this->isCreateModalOpen = true;
    }

    public function focusRecord(int $id): void
    {
        $event = Event::find($id);

        if ($event) {
            $jalali = Jalalian::fromCarbon($event->date);
            $this->currentYear = $jalali->getYear();
            $this->currentMonth = $jalali->getMonth();
            $this->selectedDate = $jalali->format('Y-m-d');
        }
    }

    public function goToToday(): void
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
        $this->selectedDate = $now->format('Y-m-d');
    }

    public function mount(): void
    {
        StateService::markViewed('calendar');

        $now = Jalalian::now();

        if (!isset($this->currentYear)) {
            $this->currentYear = $now->getYear();
            $this->currentMonth = $now->getMonth();
        }

        if (!isset($this->selectedDate)) {
            $this->selectedDate = $now->format('Y-m-d');
        }
    }

    public function nextMonth(): void
    {
        if (++$this->currentMonth > 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        }
    }

    public function openCreateModal(): void
    {
        $this->form->resetForm($this->selectedDate);
        $this->isCreateModalOpen = true;
    }

    public function prevMonth(): void
    {
        if (--$this->currentMonth < 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.tab.calendar');
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

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    #[Computed]
    public function selectedDayEvents(): Collection
    {
        if (!$this->selectedDate) {
            return collect();
        }

        try {
            $gregorianDate = Jalalian::fromFormat('Y-m-d', $this->selectedDate)->toCarbon();
            $month = $gregorianDate->month;
            $day = $gregorianDate->day;
        } catch (Throwable $e) {
            return collect();
        }

        $holidays = collect(HolidayService::getHolidaysForDate($this->selectedDate))
            ->map(fn($holiday, $index) => [
                'id' => 'holiday-' . $index,
                'type' => 'holiday',
                'title' => $holiday['title'],
                'description' => $holiday['type'] . (($holiday['hijri'] ?? null) ? ' · ' . $holiday['hijri'] : ''),
                'time' => '00:00',
                'is_owner' => false,
                'private' => false,
            ]);

        $events = Event::query()
            ->with('shares:user_id,event_id')
            ->whereDate('date', $gregorianDate)
            ->where(function ($q) {
                $authId = Auth::id();
                $q->where('user_id', $authId)
                    ->orWhere('private', false)
                    ->orWhereHas('shares', fn($sq) => $sq->where('user_id', $authId));
            })
            ->latest('date')
            ->get()
            ->map(fn($event) => [
                'id' => $event->id,
                'type' => 'event',
                'title' => $event->title,
                'description' => $event->description,
                'time' => Jalalian::fromCarbon($event->date)->format('H:i'),
                'is_owner' => $event->user_id === Auth::id(),
                'private' => $event->private,
                'is_shared' => $event->user_id !== Auth::id()
                    && $event->shares->contains('user_id', Auth::id()),
                'is_reservation_linked' => EventSyncService::isReservationEvent($event->description),
                'reservation_id' => EventSyncService::reservationIdFrom($event->description),
            ]);

        $profiles = Profile::query()
            ->select('id', 'user_id', 'birthdate', 'start_date', 'image')
            ->with('user:id,name')
            ->where(function ($q) use ($month, $day) {
                $q->where(function ($q1) use ($month, $day) {
                    $q1->whereMonth('birthdate', $month)->whereDay('birthdate', $day);
                })->orWhere(function ($q2) use ($month, $day) {
                    $q2->whereMonth('start_date', $month)->whereDay('start_date', $day);
                });
            })->get();

        $birthdays = $profiles->filter(fn($p) => $p->birthdate?->month === $month && $p->birthdate?->day === $day)
            ->map(fn($p) => [
                'id' => 'birthday-' . $p->id,
                'type' => 'birthday',
                'title' => 'تولد ' . ($p->user->name ?? 'کاربر'),
                'description' => 'تولد مبارک!',
                'time' => '00:00',
                'is_owner' => false,
                'private' => false,
                'avatar' => $p->getImageUrl(),
            ]);

        $anniversaries = $profiles->filter(fn($p) => $p->start_date?->month === $month && $p->start_date?->day === $day)
            ->map(function ($p) use ($gregorianDate) {
                $years = $p->start_date->diffInYears($gregorianDate);
                return [
                    'id' => 'anniversary-' . $p->id,
                    'type' => 'anniversary',
                    'title' => ($years > 0 ? $years . 'مین ' : '') . 'سالگرد همکاری ' . ($p->user->name ?? 'کاربر'),
                    'description' => 'سالگرد همکاری مبارک!',
                    'time' => '00:00',
                    'is_owner' => false,
                    'private' => false,
                    'avatar' => $p->getImageUrl(),
                ];
            });

        return collect()->concat($holidays)->concat($events)->concat($birthdays)->concat($anniversaries);
    }
}
