<?php

namespace App\Livewire\Dashboard\Ths;

use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Livewire\Dashboard\Ths\Actions\SetTicketEffectivenessAction;
use App\Livewire\Dashboard\Ths\Actions\SubmitTicketReplyAction;
use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Workspace extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $ticketId;
    public ReplyForm $replyForm;
    public string $assigneeId = '';

    public function mount(int $ticketId): void
    {
        $this->ticketId = $ticketId;

        abort_unless(TicketAccessPolicy::canView(Ticket::findOrFail($ticketId), auth()->user()), 403);
    }

    #[Computed]
    public function ticket(): ?Ticket
    {
        return Ticket::with(['replies.user', 'assignee', 'requester'])->find($this->ticketId);
    }

    #[Computed]
    public function canReply(): bool
    {
        return TicketAccessPolicy::canReply($this->ticket, auth()->user());
    }

    #[Computed]
    public function canAssign(): bool
    {
        return TicketAccessPolicy::canAssign($this->ticket, auth()->user());
    }

    #[Computed]
    public function canSetEffectiveness(): bool
    {
        return TicketAccessPolicy::canSetEffectiveness($this->ticket, auth()->user());
    }

    #[Computed]
    public function canClose(): bool
    {
        return TicketAccessPolicy::canClose($this->ticket, auth()->user());
    }

    #[Computed]
    public function assignableUsers(): Collection
    {
        $target = $this->ticket?->targetDepartmentId;

        if (!$target) {
            return collect();
        }

        return User::whereHas('profile', fn($q) => $q->where('department_id', $target))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function postReply(SubmitTicketReplyAction $action): void
    {
        $action->execute($this->replyForm, $this->ticket, auth()->user());

        $this->replyForm->reset();
        unset($this->ticket);
        $this->dispatch('toast', message: 'پاسخ شما ثبت شد.', type: 'success');
    }

    public function assign(AssignTicketAction $action): void
    {
        if (blank($this->assigneeId)) {
            return;
        }

        $action->execute($this->ticket, (int)$this->assigneeId, auth()->user());

        $this->assigneeId = '';
        unset($this->ticket);
        $this->dispatch('toast', message: 'تیکت با موفقیت تخصیص داده شد.', type: 'success');
    }

    public function setEffectiveness(SetTicketEffectivenessAction $action, string $score): void
    {
        $action->execute($this->ticket, $score, auth()->user());

        unset($this->ticket);
        $this->dispatch('toast', message: 'اثربخشی ثبت شد.', type: 'success');
    }

    public function closeTicket(): void
    {
        abort_unless(TicketAccessPolicy::canClose($this->ticket, auth()->user()), 403);

        if (blank($this->ticket->effectiveness)) {
            $this->dispatch('toast', message: 'برای بستن تیکت ابتدا اثربخشی را ثبت کنید.', type: 'error');
            return;
        }

        $this->ticket->update(['status' => 'closed']);

        unset($this->ticket);
        $this->dispatch('toast', message: 'تیکت با موفقیت بسته شد.', type: 'success');
    }

    public function render()
    {
        return view('livewire.dashboard.ths.workspace');
    }
}
