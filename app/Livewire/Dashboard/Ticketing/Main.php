<?php

namespace App\Livewire\Dashboard\Ticketing;

use App\Models\Ticket;
use App\Traits\TicketingState;
use App\Traits\TicketingValidation;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Main extends Component
{
    use WithFileUploads, TicketingState, TicketingValidation;

    public int $perPage = 10;

    public function mount()
    {
        $this->mountTicketingState();
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function addFileInput(): void
    {
        $this->fileInputs[] = uniqid('', true);
    }

    public function removeFileInput($key): void
    {
        unset($this->files[$key]);

        $this->fileInputs = array_values(array_filter(
            $this->fileInputs,
            fn($input) => $input !== $key
        ));
    }

    public function updated($propertyName)
    {
        if (Str::startsWith($propertyName, 'files.')) {
            $this->resetErrorBag($propertyName);
        }
    }

    public function rate($score): void
    {
        $this->satisfactionScore = (int)$score;
    }

    public function submitRating()
    {
        $this->validateRateData();
        $this->persistRate();

        $this->activeTab = 'new';
        $this->ticketToRate = null;
        $this->dispatch('toast', message: 'از بازخورد شما سپاسگزاریم.', type: 'success');
    }

    public function submitTicket()
    {
        $this->files = collect($this->files)
            ->map(fn($file) => is_array($file) ? reset($file) : $file)
            ->filter()
            ->toArray();

        $data = $this->validateTicketData();

        try {
            $paths = $this->storeAttachment();
            $this->persistTicket($data['ticket'], $paths);

            $this->reset(['ticket', 'files', 'fileInputs', 'perPage']);
            $this->ticket['requestType'] = 'support';
            $this->ticket['priority'] = 'low';
            $this->loadRequestAreas();
            $this->addFileInput();
            $this->activeTab = 'log';
            $this->direction = 'up';

            $this->dispatch('toast', message: 'درخواست شما با موفقیت ثبت شد.', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'خطایی رخ داده است. لطفا دوباره تلاش کنید.', type: 'error');
        }
    }

    protected function persistRate(): void
    {
        if (!$this->ticketToRate) return;

        $existingExtra = $this->ticketToRate->extra ?? [];
        $updatedExtra = array_merge($existingExtra, [
            'satisfaction_comment' => $this->satisfactionComment,
        ]);

        $this->ticketToRate->update([
            'satisfaction_score' => $this->satisfactionScore,
            'extra' => $updatedExtra,
        ]);
    }

    protected function persistTicket($ticketData, array $filePaths): void
    {
        Ticket::create([
            'request_type' => $ticketData['requestType'],
            'request_area' => $ticketData['requestArea'],
            'priority' => $ticketData['priority'],
            'request_subject' => $ticketData['subject'],
            'description' => $ticketData['description'],
            'requester_files' => $filePaths,
            'requester_id' => auth()->id(),
            'extra' => ['department' => $this->ticket['department'] ?? 'N/A']
        ]);
    }

    protected function storeAttachment(): array
    {
        if (empty($this->files)) return [];

        $paths = [];
        foreach ($this->files as $file) {
            $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('files/ticketing/requester', $name, 'public');
            $paths[] = ['file' => '/storage/' . $path];
        }

        return $paths;
    }

    public function getTicketsProperty()
    {
        return Ticket::where('requester_id', auth()->id())
            ->orderByRaw("FIELD(status, 'open', 'in-progress', 'closed')")
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.dashboard.ticketing.main')->extends('layouts.app')->section('content');
    }
}
