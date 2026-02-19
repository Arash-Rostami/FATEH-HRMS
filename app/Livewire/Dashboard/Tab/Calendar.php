<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Event;
use App\Models\Profile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Morilog\Jalali\Jalalian;
use Throwable;

class Calendar extends Component
{
    public int $currentYear;
    public int $currentMonth;
    public string $selectedDate;

    public bool $isCreateModalOpen = false;
    public bool $isDeleteModalOpen = false;

    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventDate = '';
    public string $eventTime = '12:00';
    public bool $eventPrivate = false;

    public ?int $editingEventId = null;
    public ?int $deletingEventId = null;

    public function mount(): void
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
        $this->selectedDate = $now->format('Y-m-d');
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
            ->whereBetween('date', [$startDate, $endDate])
            ->where(fn($q) => $q->where('user_id', Auth::id())->orWhere('private', false))
            ->get()
            ->groupBy(fn($e) => Jalalian::fromCarbon($e->date)->format('Y-m-d'));

        $profiles = Profile::select('id', 'birthdate', 'start_date')->get();

        $birthdays = $profiles->pluck('birthdate')->filter()->map->format('m-d')->flip();
        $anniversaries = $profiles->pluck('start_date')->filter()->map->format('m-d')->flip();

        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        $todayStr = Jalalian::now()->format('Y-m-d');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            try {
                $jalali = new Jalalian($this->currentYear, $this->currentMonth, $day);
                $dateString = $jalali->format('Y-m-d');
                $mdKey = $jalali->toCarbon()->format('m-d');

                $hasEvent = $monthEvents->has($dateString);
                $hasBirthday = $birthdays->has($mdKey);
                $hasAnniversary = $anniversaries->has($mdKey);

                $eventCount = ($hasEvent ? $monthEvents->get($dateString)->count() : 0) +
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
                ];
            } catch (Throwable $e) {
                $days[] = null;
            }
        }

        return $days;
    }

    #[Computed]
    public function selectedDayEvents(): Collection
    {
        if (!$this->selectedDate) return collect();

        try {
            $gregorianDate = Jalalian::fromFormat('Y-m-d', $this->selectedDate)->toCarbon();
            $month = $gregorianDate->month;
            $day = $gregorianDate->day;
        } catch (Throwable $e) {
            return collect();
        }

        $events = Event::query()
            ->whereDate('date', $gregorianDate)
            ->where(fn($q) => $q->where('user_id', Auth::id())->orWhere('private', false))
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
            ]);

        $profiles = Profile::query()
            ->select('id', 'user_id', 'birthdate', 'start_date', 'image')
            ->with('user:id,name')
            ->where(fn($q) => $q
                ->where(fn($q1) => $q1->whereMonth('birthdate', $month)->whereDay('birthdate', $day))
                ->orWhere(fn($q2) => $q2->whereMonth('start_date', $month)->whereDay('start_date', $day))
            )->get();

        $birthdays = $profiles->filter(fn($p) => $p->birthdate?->month === $month && $p->birthdate?->day === $day)
            ->map(fn($p) => [
                'id' => 'birthday-' . $p->id,
                'type' => 'birthday',
                'title' => 'تولد ' . ($p->user->name ?? 'کاربر'),
                'description' => 'تولد مبارک!',
                'time' => '00:00',
                'is_owner' => false,
                'private' => false,
                'image' => $p->image,
                'avatar' => $p->image,
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
                    'image' => $p->image,
                    'avatar' => $p->image,
                ];
            });

        return collect()->concat($events)->concat($birthdays)->concat($anniversaries);
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function nextMonth(): void
    {
        if (++$this->currentMonth > 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        }
    }

    public function prevMonth(): void
    {
        if (--$this->currentMonth < 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        }
    }

    public function goToToday(): void
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
        $this->selectedDate = $now->format('Y-m-d');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->eventDate = $this->selectedDate;
        $this->isCreateModalOpen = true;
    }

    public function editEvent(int $eventId): void
    {
        $event = Event::where('user_id', Auth::id())->find($eventId);

        if (!$event) return;

        $this->editingEventId = $eventId;
        $this->eventTitle = $event->title;
        $this->eventDescription = $event->description ?? '';
        $this->eventDate = Jalalian::fromCarbon($event->date)->format('Y-m-d');
        $this->eventTime = $event->date->format('H:i');
        $this->eventPrivate = (bool) $event->private;

        $this->isCreateModalOpen = true;
    }

    public function saveEvent(): void
    {
        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventDate' => 'required|date_format:Y-m-d',
            'eventTime' => 'required',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y-m-d H:i', "{$this->eventDate} {$this->eventTime}")->toCarbon();
        } catch (Throwable $e) {
            $this->addError('eventDate', 'Invalid Date');
            return;
        }

        Event::updateOrCreate(
            ['id' => $this->editingEventId, 'user_id' => Auth::id()],
            [
                'title' => $this->eventTitle,
                'description' => $this->eventDescription,
                'date' => $gregorianDate,
                'private' => $this->eventPrivate,
            ]
        );

        $this->isCreateModalOpen = false;
        $this->resetForm();
    }

    public function confirmDelete(int $eventId): void
    {
        $this->deletingEventId = $eventId;
        $this->isDeleteModalOpen = true;
    }

    public function deleteEvent(): void
    {
        if ($this->deletingEventId) {
            Event::where('user_id', Auth::id())->where('id', $this->deletingEventId)->delete();
        }

        $this->isDeleteModalOpen = false;
        $this->deletingEventId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['editingEventId', 'eventTitle', 'eventDescription', 'eventPrivate']);
        $this->eventDate = $this->selectedDate;
        $this->eventTime = '12:00';
    }

    public function render()
    {
        return view('livewire.dashboard.tab.calendar.index');
    }
}
