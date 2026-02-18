<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Event;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Calendar extends Component
{
    public int $currentYear;
    public int $currentMonth;
    public string $selectedDate;

    public bool $isCreateModalOpen = false;
    public bool $isDeleteModalOpen = false;

    // Form inputs
    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventDate = '';
    public string $eventTime = '12:00';
    public string $eventIcon = 'event';
    public string $eventColor = '#4e5f66';
    public bool $eventPrivate = false;

    public ?int $editingEventId = null;
    public ?int $deletingEventId = null;

    #[Locked]
    public array $availableIcons = [
        'event' => 'پیش‌فرض',
        'cake' => 'تولد',
        'celebration' => 'جشن',
        'work' => 'کاری',
        'meeting_room' => 'جلسه',
        'flight' => 'سفر',
        'restaurant' => 'غذا',
        'fitness_center' => 'ورزش',
        'school' => 'آموزش',
        'schedule' => 'مهلت',
        'payments' => 'پرداخت',
        'medical_services' => 'پزشکی'
    ];

    #[Locked]
    public array $availableColors = [
        '#4e5f66' => 'Slate',
        '#ef4444' => 'Red',
        '#f97316' => 'Orange',
        '#f59e0b' => 'Amber',
        '#84cc16' => 'Lime',
        '#22c55e' => 'Green',
        '#10b981' => 'Emerald',
        '#14b8a6' => 'Teal',
        '#06b6d4' => 'Cyan',
        '#3b82f6' => 'Blue',
        '#6366f1' => 'Indigo',
        '#8b5cf6' => 'Violet',
        '#d946ef' => 'Fuchsia',
        '#ec4899' => 'Pink',
        '#f43f5e' => 'Rose'
    ];

    public function mount()
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
        $this->selectedDate = $now->format('Y-m-d');
    }

    #[Computed]
    public function calendarDays(): array
    {
        $days = [];

        // 1. Calculate Date Range
        $firstDayJalali = new Jalalian($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $firstDayJalali->getMonthDays();
        $startDayOfWeek = $firstDayJalali->getDayOfWeek(); // 0 (Sat) to 6 (Fri)

        $startDateGregorian = $firstDayJalali->toCarbon()->startOfDay();
        $endDateGregorian = (new Jalalian($this->currentYear, $this->currentMonth, $daysInMonth))->toCarbon()->endOfDay();

        // 2. Fetch All Events for this Month (Batch Query)
        $monthEvents = Event::query()
            ->whereBetween('date', [$startDateGregorian, $endDateGregorian])
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhere('private', false);
            })
            ->get()
            ->groupBy(function ($event) {
                return Jalalian::fromCarbon($event->date)->format('Y-m-d');
            });

        // Previous month padding
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        // Days of current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDateJalali = new Jalalian($this->currentYear, $this->currentMonth, $day);
            $dateString = $currentDateJalali->format('Y-m-d');

            // Check events
            $hasEvents = $monthEvents->has($dateString);

            // Count events logic or preview dots logic could go here
            $eventCount = $hasEvents ? $monthEvents[$dateString]->count() : 0;

            $days[] = [
                'day' => $day,
                'date' => $dateString,
                'isToday' => $dateString === Jalalian::now()->format('Y-m-d'),
                'isSelected' => $dateString === $this->selectedDate,
                'hasEvents' => $hasEvents,
                'eventCount' => $eventCount
            ];
        }

        return $days;
    }

    #[Computed]
    public function selectedDayEvents(): Collection
    {
        if (!$this->selectedDate) return collect();

        try {
            $jalaliDate = Jalalian::fromFormat('Y-m-d', $this->selectedDate);
            $gregorianDate = $jalaliDate->toCarbon();
            $startOfDay = $gregorianDate->copy()->startOfDay();
            $endOfDay = $gregorianDate->copy()->endOfDay();
        } catch (\Exception $e) {
            return collect();
        }

        // 1. User Events
        $events = Event::query()
            ->whereBetween('date', [$startOfDay, $endOfDay])
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhere('private', false);
            })
            ->latest('date')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => 'event',
                    'title' => $event->title,
                    'description' => $event->description,
                    'time' => Jalalian::fromCarbon($event->date)->format('H:i'),
                    'is_owner' => $event->user_id === Auth::id(),
                    'private' => $event->private,
                    'icon' => $event->icon ?? 'event',
                    'color' => $event->color ?? '#4e5f66',
                ];
            });

        // 2. Profiles (Birthdays)
        $birthdays = Profile::query()
            ->whereMonth('birthdate', $gregorianDate->month)
            ->whereDay('birthdate', $gregorianDate->day)
            ->with('user')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => 'birthday-' . $profile->id,
                    'type' => 'birthday',
                    'title' => 'تولد ' . ($profile->user->name ?? 'کاربر'),
                    'description' => 'تولد مبارک!',
                    'time' => '00:00',
                    'is_owner' => false,
                    'private' => false,
                    'image' => $profile->image,
                    'icon' => 'cake',
                    'color' => '#ec4899', // Pink for birthday
                ];
            });

        return $events->concat($birthdays);
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
    }

    public function nextMonth()
    {
        $this->currentMonth++;
        if ($this->currentMonth > 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        }
    }

    public function prevMonth()
    {
        $this->currentMonth--;
        if ($this->currentMonth < 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        }
    }

    public function goToToday()
    {
        $now = Jalalian::now();
        $this->currentYear = $now->getYear();
        $this->currentMonth = $now->getMonth();
        $this->selectedDate = $now->format('Y-m-d');
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->eventDate = $this->selectedDate;
        $this->isCreateModalOpen = true;
    }

    public function editEvent($eventId)
    {
        $event = Event::find($eventId);
        if (!$event || $event->user_id !== Auth::id()) return;

        $this->editingEventId = $eventId;
        $this->eventTitle = $event->title;
        $this->eventDescription = $event->description ?? '';
        $this->eventDate = Jalalian::fromCarbon($event->date)->format('Y-m-d');
        $this->eventTime = $event->date->format('H:i');
        $this->eventIcon = $event->icon ?? 'event';
        $this->eventColor = $event->color ?? '#4e5f66';
        $this->eventPrivate = (bool) $event->private;

        $this->isCreateModalOpen = true;
    }

    public function saveEvent()
    {
        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventDate' => 'required|date_format:Y-m-d',
            'eventTime' => 'required',
            'eventIcon' => 'required|string',
            'eventColor' => 'required|string',
        ]);

        try {
            $jalaliDate = Jalalian::fromFormat('Y-m-d', $this->eventDate);
            $gregorianDate = $jalaliDate->toCarbon();

            list($h, $m) = explode(':', $this->eventTime);
            $gregorianDate->setTime($h, $m);

        } catch (\Exception $e) {
            $this->addError('eventDate', 'Invalid Date');
            return;
        }

        $data = [
            'title' => $this->eventTitle,
            'description' => $this->eventDescription,
            'date' => $gregorianDate,
            'private' => $this->eventPrivate,
            'icon' => $this->eventIcon,
            'color' => $this->eventColor,
        ];

        if ($this->editingEventId) {
            $event = Event::find($this->editingEventId);
            if ($event && $event->user_id === Auth::id()) {
                $event->update($data);
            }
        } else {
            $data['user_id'] = Auth::id();
            Event::create($data);
        }

        $this->isCreateModalOpen = false;
        $this->resetForm();
    }

    public function confirmDelete($eventId)
    {
        $this->deletingEventId = $eventId;
        $this->isDeleteModalOpen = true;
    }

    public function deleteEvent()
    {
        if (!$this->deletingEventId) return;

        $event = Event::find($this->deletingEventId);
        if ($event && $event->user_id === Auth::id()) {
            $event->delete();
        }

        $this->isDeleteModalOpen = false;
        $this->deletingEventId = null;
    }

    private function resetForm()
    {
        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventDescription = '';
        $this->eventDate = $this->selectedDate;
        $this->eventTime = '12:00';
        $this->eventIcon = 'event';
        $this->eventColor = '#4e5f66';
        $this->eventPrivate = false;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.calendar');
    }
}
