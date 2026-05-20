<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Services\Reservation\ValidationService;
use App\Traits\FilamentHeaderActions;
use App\Traits\FilamentPageBehavior;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditReservation extends EditRecord
{
    use FilamentPageBehavior, FilamentHeaderActions;

    protected static string $resource = ReservationResource::class;

    protected function beforeSave(): void
    {
        $data      = $this->form->getState();
        $start     = Carbon::parse($data['start_time']);
        $end       = Carbon::parse($data['end_time']);
        $isFullDay = (bool)($data['is_full_day'] ?? false);

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
    }
}
