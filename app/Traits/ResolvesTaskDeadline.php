<?php

namespace App\Traits;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

trait ResolvesTaskDeadline
{
    private function resolveDeadline(Form $form): ?Carbon
    {
        if (!$form->deadlineYear || !$form->deadlineMonth || !$form->deadlineDay) return null;

        if (!CalendarUtils::checkDate((int) $form->deadlineYear, (int) $form->deadlineMonth, (int) $form->deadlineDay, true)) {
            throw new InvalidArgumentException(__('resources/task/strings.validation.invalid_deadline'));
        }

        return CalendarUtils::createCarbonFromFormat(
            'Y/m/d H:i:s',
            sprintf('%s/%02d/%02d 12:00:00', $form->deadlineYear, $form->deadlineMonth, $form->deadlineDay)
        );
    }

    private function guardProjectDeadline(?Carbon $deadline, ?Project $project): void
    {
        if ($project?->deadlineCapExceeded($deadline)) {
            throw ValidationException::withMessages([
                'form.deadline' => __('resources/task/strings.validation.project_deadline_cap'),
            ]);
        }
    }
}
