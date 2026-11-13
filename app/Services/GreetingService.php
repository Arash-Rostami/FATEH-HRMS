<?php

namespace App\Services;

use App\Services\Template\Words\Greetings;
use Carbon\Carbon;

class GreetingService
{
    public function getGreeting(): string
    {
        $now = Carbon::now();

        return Greetings::pick($this->getTimeOfDay($now) . '_' . $this->getDayOfWeek($now));
    }

    public function getShortGreeting(string $name): string
    {
        return Greetings::pick($this->getTimeOfDay(), ['name' => $name]);
    }

    private function getTimeOfDay(?Carbon $now = null): string
    {
        $hour = ($now ?? Carbon::now())->hour;

        return match (true) {
            $hour >= 4 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 21 => 'evening',
            default => 'night',
        };
    }

    private function getDayOfWeek(?Carbon $now = null): string
    {
        $now = ($now ?? Carbon::now())->copy();

        if ($now->hour < 4) {
            $now->subDay();
        }

        return match ($now->dayOfWeek) {
            Carbon::SATURDAY => 'saturday',
            Carbon::SUNDAY => 'sunday',
            Carbon::MONDAY => 'monday',
            Carbon::TUESDAY => 'tuesday',
            Carbon::WEDNESDAY => 'wednesday',
            Carbon::THURSDAY => 'thursday',
            Carbon::FRIDAY => 'friday',
        };
    }
}
