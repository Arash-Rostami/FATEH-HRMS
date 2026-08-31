<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ReservationResource\Actions\GenerateSeriesAction;
use App\Models\Resource;
use App\Models\User;
use App\Services\Reservation\ValidationService;
use App\Traits\{FilamentDateHandler, FilamentPageBehavior};
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReservation extends CreateRecord
{
    use FilamentPageBehavior, FilamentDateHandler;

    protected static string $resource = ReservationResource::class;

    protected function datetimeFields(): array
    {
        return [
            ['field' => 'start_time', 'default_time' => '00:00'],
            ['field' => 'end_time', 'default_time' => '00:00'],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->mergeDeadline($data);

        $user = User::find($data['user_id'] ?? null);
        $resource = Resource::find($data['resource_id'] ?? null);

        if (!$user || !$resource) {
            Notification::make()->title('کاربر یا منبع یافت نشد.')
                ->danger()
                ->send();
            $this->halt();
            return $data;
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
            $data['start_time'] = $start->toDateTimeString();
            $data['end_time'] = $end->toDateTimeString();
        }

        try {
            app(ValidationService::class)->validateBooking($user, $resource, $start, $end, $isFullDay, $recurrence);
        } catch (\Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();
        if (!empty($state['is_recurring']) && !$this->record->isRange()) {
            app(GenerateSeriesAction::class)->execute(
                $this->record,
                $state['recur_pattern'] ?? 'daily',
                (int)($state['recur_count'] ?? 4)
            );
        }
    }
}
