<?php

namespace App\Filament\Resources\TaskResource\Pages\Helper;


use Carbon\Carbon;

trait HandlesTaskDeadline
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['deadline'])) {
            $dt = Carbon::parse($data['deadline']);
            $data['deadline_date'] = $dt->format('Y-m-d');
            $data['deadline_time'] = $dt->format('H:i');
        }
        return $data;
    }

    protected function mergeDeadline(array $data): array
    {
        if (filled($data['deadline_date'] ?? null)) {
            $time = $data['deadline_time'] ?? '00:00';
            $data['deadline'] = $data['deadline_date'] . ' ' . $time . ':00';
        } else {
            $data['deadline'] = null;
        }

        unset($data['deadline_date'], $data['deadline_time']);
        return $data;
    }
}
