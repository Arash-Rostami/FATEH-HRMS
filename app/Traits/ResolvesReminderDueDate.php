<?php

namespace App\Traits;

use Carbon\Carbon;
use InvalidArgumentException;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

trait ResolvesReminderDueDate
{
    private function resolveDueAt(Form $form): ?Carbon
    {
        if (!$form->dueYear || !$form->dueMonth || !$form->dueDay) return null;

        if (!CalendarUtils::checkDate((int) $form->dueYear, (int) $form->dueMonth, (int) $form->dueDay, true)) {
            throw new InvalidArgumentException('تاریخ سررسید معتبر نیست.');
        }

        $time = $form->dueTime ?: '09:00';

        return CalendarUtils::createCarbonFromFormat(
            'Y/m/d H:i:s',
            sprintf('%s/%02d/%02d %s:00', $form->dueYear, $form->dueMonth, $form->dueDay, $time)
        );
    }
}
