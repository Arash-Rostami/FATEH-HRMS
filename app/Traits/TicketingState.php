<?php

namespace App\Traits;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait TicketingState
{
    public $ticketToRate = null;
    public ?array $selectedTicket = null;
    public string $activeTab = 'new';
    public string $modalTab = 'request';
    public array $requestAreas = [];
    public array $fileInputs = [];
    public array $files = [];
    public ?int $satisfactionScore = null;
    public ?string $satisfactionComment = null;

    public array $ticket = [
        'requester' => '',
        'department' => '',
        'requestType' => 'support',
        'requestArea' => '',
        'priority' => 'low',
        'subject' => '',
        'description' => '',
    ];

    public function mountTicketingState(): void
    {
        $this->ticket['department'] = data_get(auth()->user(), 'profile.department.name', 'N/A');

        $this->addFileInput();
        $this->loadRequestAreas();
        $this->ticketToRate = $this->loadTicketToRate();

        if ($this->ticketToRate) {
            $this->activeTab = 'rate';
        }
    }

    public function getFormattedTicketId(?array $ticket): string
    {
        if (!$ticket) return '';
        return sprintf(
            'T-%s-%04d',
            Carbon::parse($ticket['created_at'])->format('ym'),
            (int)$ticket['id']
        );
    }

    public function getFormattedTimeStamp(?array $ticket, string $col): string
    {
        if (!$ticket || !isset($ticket[$col])) return 'نامشخص';
        return Carbon::parse($ticket[$col])->diffForHumans();
    }

    public function getRequestAreaLabel(string $requestType, string $requestArea): string
    {
        return (Ticket::$requestAreaOptions[$requestType] ?? [])[$requestArea] ?? 'یافت نشد';
    }

    public function loadRequestAreas(): void
    {
        $this->requestAreas = Ticket::$requestAreaOptions[$this->ticket['requestType']] ?? [];
    }

    protected function loadTicketToRate(): ?Ticket
    {
        return Ticket::where('requester_id', auth()->id())
            ->where('status', 'closed')
            ->whereNull('satisfaction_score')
            ->first();
    }

    public function updatedTicketRequestType($value): void
    {
        $this->requestAreas = Ticket::$requestAreaOptions[$value] ?? [];
        $this->ticket['requestArea'] = '';
    }

    public function viewTicket($ticketId): void
    {
        $ticket = Ticket::with('assignee')->find($ticketId);
        $this->selectedTicket = $ticket ? $ticket->toArray() : null;
        $this->modalTab = 'request';
    }
}
