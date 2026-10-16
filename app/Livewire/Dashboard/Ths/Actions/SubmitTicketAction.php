<?php

namespace App\Livewire\Dashboard\Ths\Actions;

use App\Livewire\Dashboard\Ths\Forms\TicketForm;
use App\Models\Ticket;
use App\Support\FileNamer;
use App\Traits\CleansAttachedFiles;
use App\Traits\StoresAttachedFiles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitTicketAction
{
    use CleansAttachedFiles, StoresAttachedFiles;

    public function execute(TicketForm $form): void
    {
        $form->files = array_filter($form->files);

        $form->validate();

        $form->files = array_values($form->files);

        $key = 'ths:submit:' . auth()->id() . ':' . md5($form->subject);

        if (!Cache::add($key, 1, 10)) {
            throw ValidationException::withMessages(['ticket.subject' => 'درخواستی با همین موضوع چند لحظه پیش ثبت شد.']);
        }

        try {
            Ticket::create([
                'request_type' => $form->requestType,
                'request_area' => $form->requestArea,
                'priority' => $form->priority,
                'request_subject' => $form->subject,
                'description' => $form->description,
                'requester_files' => $this->storeFiles($form->files) ?: null,
                'requester_id' => auth()->id(),
                'extra' => [
                    'department' => auth()->user()->profile?->department_id ?? 'N/A',
                    'target_department' => empty($form->targetDepartment) || $form->targetDepartment === 'N/A' ? null : $form->targetDepartment
                ]
            ]);
        } catch (Throwable $e) {
            Cache::forget($key);

            throw $e;
        }
    }

    private function storeFiles(array $files): array
    {
        $stored = [];

        try {
            foreach ($files as $file) {
                $meta = static::storeAttachment(
                    $file,
                    'ticket/requester',
                    fn($f) => FileNamer::stored('ths', $f)
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
