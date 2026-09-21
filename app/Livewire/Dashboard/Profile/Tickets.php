<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\ReleaseRequest\Presentation\ReleaseRequestPresenter;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\ReleaseRequest;
use App\Models\Ticket;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Tickets extends Component
{
    public string $activeSection = 'ths';

    public int $perPage = 10;

    public int $supportPerPage = 10;

    public function placeholder(): View
    {
        return view('livewire.dashboard.profile.tickets.placeholder');
    }

    public function switchTab(string $section): void
    {
        $this->activeSection = in_array($section, ['ths', 'support'], true) ? $section : 'ths';
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with('assignee:id,name')
            ->withCount('replies')
            ->where('requester_id', auth()->id())
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    #[Computed]
    public function supportRequests()
    {
        return ReleaseRequest::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate($this->supportPerPage);
    }

    public function loadMoreSupport(): void
    {
        $this->supportPerPage += 10;
    }

    public function render(TicketPresenter $presenter, ReleaseRequestPresenter $supportPresenter)
    {
        return view('livewire.dashboard.profile.tickets', [
            'presenter' => $presenter,
            'supportPresenter' => $supportPresenter,
        ]);
    }
}
