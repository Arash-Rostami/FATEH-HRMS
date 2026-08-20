<?php

namespace App\Filament\Resources\ResourceResource\Schemas\Helper;

use App\Enums\ResourceType;
use App\Enums\ResourceTypeIcon;
use App\Models\ReservationPolicy;
use Illuminate\Support\Str;

class TypeProvisioner
{
    public static function provision(array $data): string
    {
        $label = $data['display_label'];
        $base = Str::slug($label, '_') ?: 'type';
        $value = $base;
        $suffix = 2;

        while (true) {
            $existingLabel = ReservationPolicy::query()
                ->where('resource_type', $value)
                ->where('key', 'display_label')
                ->first()?->value;

            if ($existingLabel === null) {
                return static::insert($data, $value); // slug free — provision here
            }

            if ($existingLabel === $label) {
                return $value; // identical label already provisioned — reuse, idempotent
            }

            $value = "{$base}_{$suffix}"; // slug taken by a different label — try next suffix
            $suffix++;
        }
    }

    private static function insert(array $data, string $value): string
    {
        $now = now();
        $rows = [
            'display_label' => $data['display_label'],
            'display_icon' => $data['display_icon'],
            'display_material_icon' => ResourceTypeIcon::from($data['display_icon'])->getMaterialIcon(),
            'display_color' => $data['display_color'],
            'is_full_day' => (bool) $data['is_full_day'],
            'window_days' => 21,
            'window_hours' => 0,
            'min_duration_minutes' => 30,
            'max_duration_minutes' => 480,
            'allowed_hours_start' => '08:00',
            'allowed_hours_end' => '18:00',
            'allowed_days' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'max_per_user' => 1,
            'max_cancel_count' => 3,
            'allow_full_day' => true,
            'allow_overlap_release' => false,
            'allow_repeat' => true,
            'allow_partial_cancel' => true,
            'requires_approval' => false,
        ];

        ReservationPolicy::insert(
            collect($rows)->map(fn($fieldValue, $key) => [
                'resource_type' => $value,
                'key' => $key,
                'value' => json_encode($fieldValue),
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all()
        );

        ResourceType::forgetCache();

        return $value;
    }
}
