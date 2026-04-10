<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Livewire\Dashboard\Tab\Forms\EventForm;
use App\Models\Event;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Throwable;

class SaveEventAction
{
    public function execute(EventForm $form, int $userId): void
    {
        $validated = $form->validated();

        try {
            $gregorianDate = Jalalian::fromFormat(
                'Y-m-d H:i',
                "{$validated['date']} {$validated['time']}"
            )->toCarbon();
        } catch (Throwable $e) {
            throw new \InvalidArgumentException('Invalid Date');
        }

        Event::updateOrCreate(
            [
                'id' => $form->editingId,
                'user_id' => $userId,
            ],
            [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'date' => $gregorianDate,
                'private' => $validated['private'],
            ]
        );
    }
}
