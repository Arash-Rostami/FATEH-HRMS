<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ReservationResource\Actions\GenerateSeriesAction;
use App\Models\Resource;
use App\Models\User;
use App\Services\Reservation\ValidationService;
use App\Traits\FilamentPageBehavior;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReservation extends CreateRecord
{
    use FilamentPageBehavior;

    protected static string $resource = ReservationResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $user = User::find($data['user_id']);
        $resource = Resource::find($data['resource_id']);

        if (!$user || !$resource) return;

        $start     = Carbon::parse($data['start_time']);
        $end       = Carbon::parse($data['end_time']);
        $isFullDay = (bool)($data['is_full_day'] ?? false);

        try {
            app(ValidationService::class)->validateBooking($user, $resource, $start, $end, $isFullDay);
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();
        if (!empty($state['is_recurring'])) {
            app(GenerateSeriesAction::class)->execute(
                $this->record,
                $state['recur_pattern'] ?? 'daily',
                (int)($state['recur_count'] ?? 4)
            );
        }
    }
}
