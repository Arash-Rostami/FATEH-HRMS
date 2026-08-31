<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\TicketForm;
use App\Models\Ticket;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Str;
use Throwable;

class SubmitTicketAction
{
    use CleansAttachedFiles, StoresAttachedFiles;

    public function execute(TicketForm $form): void
    {
        $form->validate();

        $form->files = collect($form->files)->filter()->values()->all();

        Ticket::create([
            'request_type' => $form->requestType,
            'request_area' => $form->requestArea,
            'priority' => $form->priority,
            'request_subject' => $form->subject,
            'description' => $form->description,
            'requester_files' => $this->storeFiles($form->files) ?: null,
            'requester_id' => auth()->id(),
            'extra' => [
                'department' => $form->department ?? 'N/A',
                'target_department' => empty($form->targetDepartment) || $form->targetDepartment === 'N/A' ? null : $form->targetDepartment
            ]
        ]);
    }

    private function storeFiles(array $files): array
    {
        $stored = [];

        try {
            foreach ($files as $file) {
                $meta = static::storeAttachment(
                    $file,
                    'ticket/requester',
                    fn($f) => time() . '_' . Str::random(10) . '.' . $f->extension()
                );

                $stored[] = [
                    'file' => $meta['path'],
                    'name' => $meta['name'],
                    'mime' => $meta['mime'],
                    'size' => $meta['size'],
                ];
            }
        } catch (Throwable $e) {
            static::deleteStoredFiles(collect($stored)->pluck('file')->all());

            throw $e;
        }

        return $stored;
    }
}
