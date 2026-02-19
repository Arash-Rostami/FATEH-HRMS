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
    public bool $eventPrivate = false;

    public ?int $editingEventId = null;
    public ?int $deletingEventId = null;

    public function mount()
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
        } catch (\Exception $e) {
            return '';
        }
    }

    #[Computed]
    public function calendarDays(): array
    {
        $days = [];

        try {
            // 1. Calculate Date Range
            $firstDayJalali = new Jalalian($this->currentYear, $this->currentMonth, 1);
            $daysInMonth = $firstDayJalali->getMonthDays();
            $startDayOfWeek = $firstDayJalali->getDayOfWeek(); // 0 (Sat) to 6 (Fri)

            $startDateGregorian = $firstDayJalali->toCarbon()->startOfDay();
            $endDateGregorian = (new Jalalian($this->currentYear, $this->currentMonth, $daysInMonth))->toCarbon()->endOfDay();
        } catch (\Exception $e) {
            return [];
        }

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

        // 3. Fetch Profiles for Birthdays and Anniversaries
        // Optimization: Fetch all profiles once and filter in memory since user count is manageable
        $profiles = Profile::select('id', 'user_id', 'birthdate', 'start_date', 'image')
            ->with('user:id,name')
            ->get();

        // Previous month padding
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        // Days of current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            try {
                $currentDateJalali = new Jalalian($this->currentYear, $this->currentMonth, $day);
                $dateString = $currentDateJalali->format('Y-m-d');
                $gregorianDate = $currentDateJalali->toCarbon();

                // For comparisons, we only care about month and day for recurring events
                $currentJalaliMonth = $this->currentMonth;
                $currentJalaliDay = $day;

                // However, profiles store Gregorian dates. We need to check if the Gregorian date of this Jalali day matches the Gregorian month/day of the birthdate/anniversary.
                // This is complex due to leap years. A simpler approach is to convert the profile dates to Jalali and compare month/day.
                // Let's stick to Gregorian month/day comparison for simplicity as it handles most cases well enough, though strictly speaking Jalali birthdays should follow Jalali calendar.
                // Given the library usage, let's convert the profile date to Jalali to check month/day if that's the requirement, OR stick to Gregorian match.
                // Most systems match Gregorian M/D. Let's stick to Gregorian M/D match for now as Profile stores Gregorian.

                $gregorianMonth = $gregorianDate->month;
                $gregorianDay = $gregorianDate->day;

                // Check events
                $dailyEvents = $monthEvents->get($dateString, collect());
                $hasEvent = $dailyEvents->isNotEmpty();
                $eventCount = $dailyEvents->count();

                // Check Birthdays (Ignore Year)
                $hasBirthday = $profiles->contains(function ($profile) use ($gregorianMonth, $gregorianDay) {
                    return $profile->birthdate &&
                        $profile->birthdate->month === $gregorianMonth &&
                        $profile->birthdate->day === $gregorianDay;
                });

                // Check Anniversaries (Ignore Year)
                $hasAnniversary = $profiles->contains(function ($profile) use ($gregorianMonth, $gregorianDay) {
                    return $profile->start_date &&
                        $profile->start_date->month === $gregorianMonth &&
                        $profile->start_date->day === $gregorianDay;
                });

                if ($hasBirthday) $eventCount++;
                if ($hasAnniversary) $eventCount++;

                $days[] = [
                    'day' => $day,
                    'date' => $dateString,
                    'isToday' => $dateString === Jalalian::now()->format('Y-m-d'),
                    'isSelected' => $dateString === $this->selectedDate,
                    'hasEvents' => $hasEvent,
                    'hasBirthday' => $hasBirthday,
                    'hasAnniversary' => $hasAnniversary,
                    'eventCount' => $eventCount,
                ];
            } catch (\Exception $e) {
                // Skip invalid dates
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
            $jalaliDate = Jalalian::fromFormat('Y-m-d', $this->selectedDate);
            $gregorianDate = $jalaliDate->toCarbon();
            $startOfDay = $gregorianDate->copy()->startOfDay();
            $endOfDay = $gregorianDate->copy()->endOfDay();

            $gregorianMonth = $gregorianDate->month;
            $gregorianDay = $gregorianDate->day;
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
                ];
            });

        // 2. Profiles (Birthdays - Ignore Year)
        $birthdays = Profile::query()
            ->whereMonth('birthdate', $gregorianMonth)
            ->whereDay('birthdate', $gregorianDay)
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
                    'avatar' => $profile->image,
                ];
            });

        // 3. Profiles (Anniversaries - Ignore Year)
        $anniversaries = Profile::query()
            ->whereMonth('start_date', $gregorianMonth)
            ->whereDay('start_date', $gregorianDay)
            ->with('user')
            ->get()
            ->map(function ($profile) use ($gregorianDate) {
                $years = $profile->start_date->diffInYears($gregorianDate);
                return [
                    'id' => 'anniversary-' . $profile->id,
                    'type' => 'anniversary',
                    'title' => ($years > 0 ? $years . 'مین ' : '') . 'سالگرد همکاری ' . ($profile->user->name ?? 'کاربر'),
                    'description' => 'سالگرد همکاری مبارک!',
                    'time' => '00:00',
                    'is_owner' => false,
                    'private' => false,
                    'image' => $profile->image,
                    'avatar' => $profile->image,
                ];
            });

        return $events->concat($birthdays)->concat($anniversaries);
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
        $this->eventPrivate = (bool) $event->private;

        $this->isCreateModalOpen = true;
    }

    public function saveEvent()
    {
        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventDate' => 'required|date_format:Y-m-d',
            'eventTime' => 'required',
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

        if ($this->editingEventId) {
            $event = Event::find($this->editingEventId);
            if ($event && $event->user_id === Auth::id()) {
                $event->update([
                    'title' => $this->eventTitle,
                    'description' => $this->eventDescription,
                    'date' => $gregorianDate,
                    'private' => $this->eventPrivate,
                ]);
            }
        } else {
            Event::create([
                'user_id' => Auth::id(),
                'title' => $this->eventTitle,
                'description' => $this->eventDescription,
                'date' => $gregorianDate,
                'private' => $this->eventPrivate,
            ]);
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
        $this->eventPrivate = false;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.calendar.index');
    }
}
