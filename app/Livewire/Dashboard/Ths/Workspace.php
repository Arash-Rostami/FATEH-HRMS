<?php

namespace App\Livewire\Dashboard\Ths;

use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Livewire\Dashboard\Ths\Actions\ReleaseTicketAction;
use App\Livewire\Dashboard\Ths\Actions\RerouteTicketAction;
use App\Livewire\Dashboard\Ths\Actions\SetTicketEffectivenessAction;
use App\Livewire\Dashboard\Ths\Actions\SubmitTicketReplyAction;
use App\Livewire\Dashboard\Ths\Actions\WithdrawTicketAction;
use App\Livewire\Dashboard\Ths\Forms\ReplyForm;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Department;
use App\Models\Ticket;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Workspace extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $ticketId;
    #[Locked]
    public string $seenStatus = '';
    public ReplyForm $replyForm;
    public string $assigneeId = '';
    public string $rerouteTo = '';

    public function mount(int $ticketId): void
    {
        $this->ticketId = $ticketId;

        $ticket = Ticket::findOrFail($ticketId);

        abort_unless(TicketAccessPolicy::canView($ticket, auth()->user()), 403);

        $this->seenStatus = $ticket->status;
    }

    #[Computed]
    public function ticket(): ?Ticket
    {
        return Ticket::with(['replies.user.profile', 'assignee', 'requester'])->find($this->ticketId);
    }

    #[Computed]
    public function canReply(): bool
    {
        return $this->ticket && TicketAccessPolicy::canReply($this->ticket, auth()->user());
    }

    #[Computed]
    public function canAssign(): bool
    {
        return $this->ticket && TicketAccessPolicy::canAssign($this->ticket, auth()->user());
    }

    #[Computed]
    public function canSetEffectiveness(): bool
    {
        return $this->ticket && TicketAccessPolicy::canSetEffectiveness($this->ticket, auth()->user());
    }

    #[Computed]
    public function canClose(): bool
    {
        return $this->ticket && TicketAccessPolicy::canClose($this->ticket, auth()->user());
    }

    #[Computed]
    public function canWithdraw(): bool
    {
        return $this->ticket && TicketAccessPolicy::canWithdraw($this->ticket, auth()->user());
    }

    #[Computed]
    public function canReroute(): bool
    {
        return $this->ticket && TicketAccessPolicy::canReroute($this->ticket, auth()->user());
    }

    #[Computed]
    public function canRelease(): bool
    {
        return $this->ticket && TicketAccessPolicy::canRelease($this->ticket, auth()->user());
    }

    #[Computed]
    public function departmentOptions(): Collection
    {
        return Department::withHeads(Department::getCachedOptions());
    }

    #[Computed]
    public function assignableUsers(): Collection
    {
        return $this->ticket?->assignableUsers()
            ->with('profile')
            ->orderBy('name')
            ->get(['id', 'name', 'presence', 'status', 'last_seen', 'focus_until']) ?? collect();
    }

    public function postReply(SubmitTicketReplyAction $action): void
    {
        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
            return;
        }

        if (! $this->runGuarded(fn () => $action->execute($this->replyForm, $this->ticket, auth()->user()))) {
            return;
        }

        $this->replyForm->reset();
        unset($this->ticket);
        $this->dispatch('toast', message: 'پاسخ شما ثبت شد.', type: 'success');
        $this->dispatch('ths-reply-posted');
        $this->dispatch('ths-ticket-updated');
    }

    public function removeReplyAttachment(int $index): void
    {
        unset($this->replyForm->files[$index]);
        $this->replyForm->files = array_values($this->replyForm->files);
    }

    public function assign(AssignTicketAction $action): void
    {
        if (blank($this->assigneeId)) {
            return;
        }

        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
            return;
        }

        if (! $this->runGuarded(fn () => $action->execute($this->ticket, (int)$this->assigneeId, auth()->user(), $this->seenStatus))) {
            unset($this->ticket);
            $this->seenStatus = $this->ticket?->status ?? '';
            $this->dispatch('ths-ticket-updated');
            return;
        }

        $this->assigneeId = '';
        unset($this->ticket);
        $this->seenStatus = $this->ticket->status;
        $this->dispatch('toast', message: 'تیکت با موفقیت تخصیص داده شد.', type: 'success');
        $this->dispatch('ths-ticket-updated');
    }

    public function setEffectiveness(SetTicketEffectivenessAction $action, string $score): void
    {
        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
            return;
        }

        if (! $this->runGuarded(fn () => $action->execute($this->ticket, $score, auth()->user()))) {
            return;
        }

        unset($this->ticket);
        $this->dispatch('toast', message: 'اثربخشی ثبت شد.', type: 'success');
        $this->dispatch('ths-ticket-updated');
    }

    public function closeTicket(AssignTicketAction $action): void
    {
        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
            return;
        }

        if (blank($this->ticket->effectiveness)) {
            $this->dispatch('toast', message: 'برای بستن تیکت ابتدا اثربخشی را ثبت کنید.', type: 'error');
            return;
        }

        if (! $this->runGuarded(fn () => $action->close($this->ticket, auth()->user()))) {
            return;
        }

        unset($this->ticket);
        $this->dispatch('toast', message: 'تیکت با موفقیت بسته شد.', type: 'success');
        $this->dispatch('ths-ticket-updated');
    }

    public function withdrawTicket(WithdrawTicketAction $action): void
    {
        $this->finishAction(fn () => $action->execute($this->ticket, auth()->user()), 'درخواست شما لغو شد.');
    }

    public function releaseTicket(ReleaseTicketAction $action): void
    {
        $this->finishAction(fn () => $action->execute($this->replyForm, $this->ticket, auth()->user()), 'تیکت به صف بازگردانده شد.');
    }

    public function rerouteTicket(RerouteTicketAction $action): void
    {
        $this->finishAction(fn () => $action->execute($this->replyForm, $this->ticket, $this->rerouteTo, auth()->user()), 'تیکت ارجاع داده شد.');
    }

    private function finishAction(callable $fn, string $message): void
    {
        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
            return;
        }

        if (! $this->runGuarded($fn)) {
            return;
        }

        $this->replyForm->reset();
        $this->rerouteTo = '';
        unset($this->ticket);
        $this->dispatch('toast', message: $message, type: 'success');
        $this->dispatch('ths-ticket-updated');
    }

    private function runGuarded(callable $fn): bool
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            throw $e;
        } catch (HttpException $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'دسترسی لازم برای این عملیات وجود ندارد.', type: 'error');
            return false;
        }

        return true;
    }

    public function render()
    {
        if (! $this->ticket) {
            $this->dispatch('ths-ticket-updated');
        }

        return view('livewire.dashboard.ths.workspace', ['presenter' => new TicketPresenter()]);
    }
}
