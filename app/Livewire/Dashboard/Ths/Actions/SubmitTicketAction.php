<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\TicketForm;
use App\Models\Ticket;
use Illuminate\Support\Str;

class SubmitTicketAction
{
    public function execute(TicketForm $form): void
    {
        $form->files = collect($form->files)
            ->map(fn($f) => is_array($f) ? reset($f) : $f)
            ->filter()->toArray();

        Ticket::create([
            'request_type'    => $form->requestType,
            'request_area'    => $form->requestArea,
            'priority'        => $form->priority,
            'request_subject' => $form->subject,
            'description'     => $form->description,
            'requester_files' => $this->storeFiles($form->files) ?: null,
            'requester_id'    => auth()->id(),
            'extra'           => ['department' => $form->department ?? 'N/A'],
        ]);
    }

    private function storeFiles(array $files): array
    {
        return collect($files)->map(function ($file) {
            $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ticket/requester', $name, 'public');
            return ['file' => $path];
        })->values()->all();
    }
}
