<?php

namespace App\Livewire\Dashboard\Ths;

use App\Livewire\Dashboard\Ths\Actions\SubmitRatingAction;
use App\Livewire\Dashboard\Ths\Actions\SubmitTicketAction;
use App\Livewire\Dashboard\Ths\Forms\RatingForm;
use App\Livewire\Dashboard\Ths\Forms\TicketForm;
use App\Livewire\Dashboard\Ths\Presentation\TicketPresenter;
use App\Models\Department;
use App\Models\Ticket;
use App\Support\TicketAccessPolicy;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class Main extends Component
{
    use FocusOnRecord;
    use WithFileUploads;

    public TicketForm $ticket;
    public RatingForm $rating;

    #[Locked]
    public $ticketToRate = null;
    public ?array $selectedTicket = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'new';
    public string $direction = 'up';
    public string $modalTab = 'request';
    public array $requestAreas = [];
    public array $departmentOptions = [];

    #[Url(as: 'queue')]
    public string $listFilter = 'mine';
    public int $perPage = 10;
    public int $inboxPerPage = 10;
    public string $ticketSearch = '';

    public function addFileInput(): void
    {
        $this->ticket->fileInputs[] = Str::random(13);
    }

    public function focusRecord(int $id): void
    {
        $ticket = Ticket::find($id);

        if ($ticket && TicketAccessPolicy::canView($ticket, auth()->user())) {
            $this->viewTicket($id);
            $this->modalTab = 'response';
            $this->dispatch('ths-modal');
        }
    }

    public function loadMore(): void
    {
        if ($this->listFilter === 'actionable') {
            $this->inboxPerPage += 10;
        } else {
            $this->perPage += 10;
        }
    }

    public function setListFilter(string $filter): void
    {
        if (!in_array($filter, ['mine', 'actionable'], true) || $this->listFilter === $filter) {
            return;
        }

        $this->listFilter = $filter;
        $this->perPage = 10;
        $this->inboxPerPage = 10;
    }

    public function toggleActionableFilter(): void
    {
        $this->setListFilter($this->listFilter === 'actionable' ? 'mine' : 'actionable');
    }

    #[Computed]
    public function activeTickets()
    {
        return $this->listFilter === 'actionable' ? $this->inboxTickets : $this->tickets;
    }

    public function mount(): void
    {
        $this->ticket->department = data_get(auth()->user(), 'profile.department_id', 'N/A');
        $this->ticket->fileInputs[] = Str::random(13);
        $this->ticket->requestTypeOptions = Ticket::$requestTypeOptions;
        $this->departmentOptions = Department::getCachedOptionsExcludingEmptyTickets()->toArray();
        $this->loadRequestAreas();

        $this->ticketToRate = Ticket::where('requester_id', auth()->id())
            ->where('status', 'closed')
            ->whereNull('satisfaction_score')
            ->first();

        if ($this->ticketToRate) {
            $this->activeTab = 'rate';
        }
    }

    public function rate($score): void
    {
        $this->rating->score = (int)$score;
    }

    public function removeFileInput($key): void
    {
        unset($this->ticket->files[$key]);
        $this->ticket->fileInputs = array_values(array_filter(
            $this->ticket->fileInputs, fn($i) => $i !== $key
        ));
    }

    public function render()
    {
        return view('livewire.dashboard.ths', [
            'presenter' => new TicketPresenter(),
        ])->extends('layouts.app')->section('content');
    }

    public function submitRating(SubmitRatingAction $action): void
    {
        $action->execute($this->rating, $this->ticketToRate);

        $this->ticketToRate = null;
        $this->activeTab = 'new';
        $this->direction = 'up';
        $this->dispatch('toast', message: 'از بازخورد شما سپاسگزاریم.', type: 'success');
    }

    public function submitTicket(SubmitTicketAction $action): void
    {
        try {
            $action->execute($this->ticket);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: 'ثبت درخواست ناموفق بود. لطفاً دوباره تلاش کنید.', type: 'error');
            return;
        }

        $this->ticket->reset();
        $this->ticket->department = data_get(auth()->user(), 'profile.department_id', 'N/A');
        $this->ticket->requestType = 'support';
        $this->ticket->priority = 'low';
        $this->ticket->fileInputs[] = Str::random(13);
        $this->loadRequestAreas();

        $this->activeTab = 'log';
        $this->direction = 'up';
        $this->dispatch('toast', message: 'درخواست شما با موفقیت ثبت شد.', type: 'success');
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['request', 'response'])) {
            $this->modalTab = $tab;
            return;
        }

        if ($this->activeTab === $tab) return;

        $tabs = $this->ticketToRate ? ['rate', 'log'] : ['new', 'log'];
        $curr = array_search($this->activeTab, $tabs);
        $next = array_search($tab, $tabs);

        if ($curr !== false && $next !== false) {
            $this->direction = $next > $curr ? 'down' : 'up';
        }

        $this->activeTab = $tab;
    }

    #[Computed]
    public function inboxTickets()
    {
        return Ticket::query()
            ->with('assignee', 'requester')
            ->actionableBy(auth()->user())
            ->orderByDesc('created_at')
            ->paginate($this->inboxPerPage, ['*'], 'inboxPage');
    }

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with('assignee')
            ->where('requester_id', auth()->id())
            ->when($this->ticketSearch, fn($q) => $q->where(function ($sub) {
                $term = "%{$this->ticketSearch}%";
                $sub->where('request_subject', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereRaw("CONCAT('TN-', DATE_FORMAT(created_at, '%y%m'), '-', LPAD(id, 6, '0')) LIKE ?", [$term]);
            }))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function totalTickets(): int
    {
        return $this->tickets->total();
    }

    public function updated($prop): void
    {
        if (Str::startsWith($prop, 'ticket.files.')) {
            $this->resetErrorBag($prop);
        }
    }

    public function updatedTicketRequestType($value): void
    {
        $this->loadRequestAreas();
        $this->ticket->requestArea = '';
    }

    public function updatedTicketTargetDepartment($value): void
    {
        $deptCode = $value !== 'N/A' ? $value : null;
        $this->ticket->requestTypeOptions = Ticket::getCustomRequestTypeOptions($deptCode);

        if (!array_key_exists($this->ticket->requestType, $this->ticket->requestTypeOptions)) {
            $this->ticket->requestType = array_key_first($this->ticket->requestTypeOptions) ?? 'support';
        }

        $this->loadRequestAreas();
        $this->ticket->requestArea = '';
    }
    public function viewTicket($ticketId): void
    {
        $ticket = Ticket::with('assignee')->find($ticketId);

        if (!$ticket || !TicketAccessPolicy::canView($ticket, auth()->user())) {
            return;
        }

        $this->selectedTicket = $ticket->toArray();
        $this->selectedTicket['requester_files'] = $this->resolveTicketFileUrls($this->selectedTicket['requester_files'] ?? []);
        $this->selectedTicket['assignee_files'] = $this->resolveTicketFileUrls($this->selectedTicket['assignee_files'] ?? []);
        $this->modalTab = 'request';
    }

    private function resolveTicketFileUrls(array $files): array
    {
        return array_map(
            fn ($file) => [...(array) $file, 'file_url' => Ticket::resolvePublicAssetUrl($file['file'] ?? null)],
            $files,
        );
    }

    protected function recordFocusType(): string
    {
        return 'ticket';
    }

    private function loadRequestAreas(): void
    {
        $deptCode = $this->ticket->targetDepartment !== 'N/A' ? $this->ticket->targetDepartment : null;
        $options = Ticket::getCustomRequestAreaOptions($deptCode, $this->ticket->requestType);
        $this->requestAreas = empty($options) ? (Ticket::$requestAreaOptions[$this->ticket->requestType] ?? []) : $options;
    }
}
