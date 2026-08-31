<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Services\Reservation\ValidationService;
use App\Traits\FilamentDateHandler;
use App\Traits\FilamentEditHeading;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReservation extends EditRecord
{
    use FilamentEditHeading, FilamentPageBehavior, FilamentHeaderActions, FilamentDateHandler;

    protected static string $resource = ReservationResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'start_time', 'default_time' => '00:00'],
            ['field' => 'end_time', 'default_time' => '00:00'],
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->mergeDeadline($data);

        $isFullDay = (bool)($data['is_full_day'] ?? false);
        $start = Carbon::parse($data['start_time']);
        $end = filled($data['end_time'] ?? null) ? Carbon::parse($data['end_time']) : $start->copy()->endOfDay();
        $isRange = !$isFullDay && $start->diffInDays($end) >= 1;

        if ($isRange) {
            $data['is_full_day'] = false;
        } elseif ($isFullDay) {
            $start->startOfDay();
            $end = $start->copy()->endOfDay();
            $data['start_time'] = $start->toDateTimeString();
            $data['end_time'] = $end->toDateTimeString();
        }

        try {
            app(ValidationService::class)->validateEdit(
                $this->record,
                auth()->user(),
                $start,
                $end,
                $isFullDay
            );
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }

        return $data;
    }
}
