<?php

namespace App\Livewire\Dashboard\Reminder;

use App\Livewire\Dashboard\Reminder\Actions\CompleteReminderAction;
use App\Livewire\Dashboard\Reminder\Actions\CreateReminderAction;
use App\Livewire\Dashboard\Reminder\Actions\DeleteReminderAction;
use App\Livewire\Dashboard\Reminder\Actions\SnoozeReminderAction;
use App\Livewire\Dashboard\Reminder\Actions\StopRecurrenceAction;
use App\Livewire\Dashboard\Reminder\Actions\UpdateReminderAction;
use App\Livewire\Dashboard\Reminder\Forms\ReminderForm;
use App\Livewire\Dashboard\Reminder\Presentation\ReminderPresenter;
use App\Models\Reminder;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Main extends Component
{
    #[Locked]
    public ?string $remindableType = null;

    #[Locked]
    public ?int $remindableId = null;

    public string $filter = 'active';

    public int $perPage = 10;

    public string $activeTab = 'list';

    public ?int $editingId = null;

    public bool $show = false;

    public string $tooltipPosition = 'bottom';

    public string $variant = 'icon';

    public ReminderForm $form;

    public function mount($for = null, string $tooltipPosition = 'bottom', string $variant = 'icon'): void
    {
        if ($for instanceof Model) {
            $this->remindableType = $for->getMorphClass();
            $this->remindableId = $for->getKey();
        }

        $this->tooltipPosition = $tooltipPosition;
        $this->variant = $variant;
    }

    #[On('open-reminders')]
    public function openFromShortcut(): void
    {
        if ($this->remindableType !== null || $this->variant === 'embedded') {
            return;
        }

        $this->open();
    }

    public function open(): void
    {
        $this->show = true;

        if ($existing = $this->existingHostReminder()) {
            $this->edit($existing->id);

            return;
        }

        $this->openCreate();
    }

    private function existingHostReminder(): ?Reminder
    {
        if ($this->remindableType === null) {
            return null;
        }

        return Reminder::forUser((int) auth()->id())
            ->where('remindable_type', $this->remindableType)
            ->where('remindable_id', $this->remindableId)
            ->whereNull('completed_at')
            ->orderBy('due_at')
            ->first();
    }

    public function hostUrl(): ?string
    {
        return Reminder::urlFor($this->remindableType, $this->remindableId);
    }

    public function hostIcon(): string
    {
        return ReminderPresenter::hostIconFor($this->remindableType);
    }

    #[Computed]
    public function reminders(): LengthAwarePaginator
    {
        $query = Reminder::forUser(auth()->id())
            ->when($this->remindableType, fn ($q) => $q
                ->where('remindable_type', $this->remindableType)
                ->where('remindable_id', $this->remindableId)
            );

        if ($this->filter === 'completed') {
            return $query->whereNotNull('completed_at')->orderByDesc('completed_at')->paginate($this->perPage);
        }

        if ($this->filter === 'snoozed') {
            return $query->whereNull('completed_at')
                ->where('snoozed_until', '>', now())
                ->orderBy('snoozed_until')
                ->paginate($this->perPage);
        }

        $query = match ($this->filter) {
            'today' => $query->dueToday(),
            'overdue' => $query->overdue(),
            'week' => $query->thisWeek(),
            default => $query->active(),
        };

        return $query->orderBy('due_at')->paginate($this->perPage);
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    #[Computed]
    public function suggestedTitles(): array
    {
        return Reminder::suggestedTitles(auth()->id());
    }

    #[Computed]
    public function dueCount(): int
    {
        return Reminder::dueBadgeCount(auth()->id(), $this->remindableType, $this->remindableId);
    }

    #[Computed]
    public function hasActiveReminder(): bool
    {
        return $this->existingHostReminder() !== null;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->perPage = 10;
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->editingId = null;

        if ($due = $this->hostDueDate()) {
            $this->seedDueFields($due);
        }

        $this->activeTab = 'form';
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'form' && $this->editingId === null) {
            $this->openCreate();

            return;
        }

        $this->activeTab = in_array($tab, ['list', 'form'], true) ? $tab : 'list';
    }

    public function edit(int $id): void
    {
        $reminder = $this->getReminder($id);

        abort_if($reminder->user_id !== (int) auth()->id(), 403);

        $this->form->title = $reminder->title;
        $this->form->notes = $reminder->notes ?? '';
        $this->seedDueFields($reminder->due_at);
        $this->form->recurs = $reminder->recurs->value;
        $this->form->fromChannels($reminder->channels);

        $this->editingId = $id;
        $this->activeTab = 'form';
    }

    public function create(CreateReminderAction $action): void
    {
        $action->execute($this->form, (int) auth()->id(), $this->remindableType, $this->remindableId);

        $this->closeForm();
    }

    public function update(UpdateReminderAction $action): void
    {
        $action->execute($this->getReminder($this->editingId), $this->form, (int) auth()->id());

        $this->closeForm();
    }

    public function delete(int $id, DeleteReminderAction $action): void
    {
        $action->execute($this->getReminder($id), (int) auth()->id());

        $this->dispatch('reminder-changed');
    }

    public function complete(int $id, CompleteReminderAction $action): void
    {
        $action->execute($this->getReminder($id), (int) auth()->id());

        $this->dispatch('reminder-changed');
    }

    public function snooze(int $id, string $preset, SnoozeReminderAction $action): void
    {
        $until = match ($preset) {
            '1h' => now()->addHour(),
            'tonight' => now()->setTime(18, 0),
            'tomorrow' => now()->addDay()->setTime(9, 0),
            'nextweek' => now()->addWeek(),
            default => now()->addHour(),
        };

        $action->execute($this->getReminder($id), (int) auth()->id(), $until);

        $this->dispatch('reminder-changed');
    }

    public function snoozeHours(int $id, int $hours, SnoozeReminderAction $action): void
    {
        $hours = max(1, min(12, $hours));

        $action->execute($this->getReminder($id), (int) auth()->id(), now()->addHours($hours));

        $this->dispatch('reminder-changed');
    }

    public function stopRecurrence(int $id, StopRecurrenceAction $action): void
    {
        $action->execute($this->getReminder($id), (int) auth()->id());

        $this->dispatch('reminder-changed');
    }

    #[On('reminder-changed')]
    public function onReminderChanged(): void
    {
    }

    public function presenter(Reminder $reminder): ReminderPresenter
    {
        return new ReminderPresenter($reminder);
    }

    public function render()
    {
        return view('livewire.dashboard.reminder', [
            'filterOptions' => ReminderPresenter::filterOptions(),
            'columns' => ReminderPresenter::columns(),
            'snoozePresets' => ReminderPresenter::snoozePresets(),
            'channelToggles' => ReminderPresenter::channelToggles(),
        ]);
    }

    private function getReminder(int $id): Reminder
    {
        return Reminder::query()->findOrFail($id);
    }

    private function hostDueDate(): ?Carbon
    {
        $column = match ($this->remindableType) {
            Task::class => 'deadline',
            Ticket::class => 'completion_deadline',
            Reservation::class => 'start_time',
            default => null,
        };

        if ($column === null || $this->remindableId === null) {
            return null;
        }

        $value = $this->remindableType::query()->whereKey($this->remindableId)->value($column);

        return $value ? Carbon::parse($value) : null;
    }

    private function seedDueFields(Carbon $due): void
    {
        $jDate = Jalalian::fromCarbon($due);

        $this->form->dueYear = (string) $jDate->getYear();
        $this->form->dueMonth = (string) $jDate->getMonth();
        $this->form->dueDay = (string) $jDate->getDay();
        $this->form->dueTime = $due->format('H:i');
    }

    private function closeForm(): void
    {
        $this->form->reset();
        $this->editingId = null;
        $this->activeTab = 'list';

        $this->dispatch('reminder-changed');
    }
}
