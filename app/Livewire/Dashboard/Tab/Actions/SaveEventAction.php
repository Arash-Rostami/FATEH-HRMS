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
            $date = sprintf('%04d-%02d-%02d', $validated['dateYear'], $validated['dateMonth'], $validated['dateDay']);
            $gregorianDate = Jalalian::fromFormat(
                'Y-m-d H:i',
                "{$date} {$validated['time']}"
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
                'duration_minutes' => $validated['durationMinutes'],
                'private' => $validated['private'],
                'remind_hours' => $validated['remindHours'],
            ]
        );
    }
}
