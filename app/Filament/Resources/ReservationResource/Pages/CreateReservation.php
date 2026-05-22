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

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $user = User::find($data['user_id'] ?? null);
        $resource = Resource::find($data['resource_id'] ?? null);

        if (!$user || !$resource) {
            Notification::make()->title('کاربر یا منبع یافت نشد.')
                ->danger()
                ->send();
            $this->halt();
            return;
        }

        $isFullDay = (bool)($data['is_full_day'] ?? false);
        $rawState = $this->form->getRawState();
        $recurrence = !empty($rawState['is_recurring'])
            ? ['pattern' => $rawState['recur_pattern'] ?? 'daily', 'count' => (int)($rawState['recur_count'] ?? 4)] : null;

        $start = $isFullDay
            ? Carbon::parse($data['start_time'] ?? 'today')->startOfDay()
            : Carbon::parse($data['start_time']);

        $end = $isFullDay
            ? Carbon::parse($data['end_time'] ?? $data['start_time'] ?? 'today')->endOfDay()
            : Carbon::parse($data['end_time']);

        if ($isFullDay) {
            $this->data['start_time'] = $start->toDateTimeString();
            $this->data['end_time'] = $end->toDateTimeString();
        }

        try {
            app(ValidationService::class)->validateBooking($user, $resource, $start, $end, $isFullDay, $recurrence);
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }
    }
}
