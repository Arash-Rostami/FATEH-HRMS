<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait FilamentDateHandler
{
    /**
     * Each entry: ['field' => 'deadline', 'default_time' => '00:00']
     */
    protected function datetimeFields(): array
    {
        return [
            ['field' => 'deadline', 'default_time' => '00:00'],
        ];
    }

    protected function mergeDeadline(array $data): array
    {
        foreach ($this->datetimeFields() as ['field' => $field, 'default_time' => $defaultTime]) {
            if (filled($data["{$field}_date"] ?? null)) {
                $time = $data["{$field}_time"] ?? $defaultTime;
                $data[$field] = $data["{$field}_date"] . ' ' . $time . ':00';
            } else {
                $data[$field] = null;
            }

            unset($data["{$field}_date"], $data["{$field}_time"]);
        }
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->datetimeFields() as ['field' => $field, 'default_time' => $_]) {
            if (!empty($data[$field])) {
                $dt = Carbon::parse($data[$field]);
                $data["{$field}_date"] = $dt->format('Y-m-d');
                $data["{$field}_time"] = $dt->format('H:i');
            }
        }
        return $data;
    }
}
