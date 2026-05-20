<?php

namespace Database\Seeders;

use App\Models\ReservationPolicy;
use Illuminate\Database\Seeder;

class ReservationPolicySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'window_days'          => 21,
            'window_hours'         => 2,
            'max_per_user'         => 1,
            'max_cancel_count'     => 3,
            'allow_full_day'       => true,
            'allow_repeat'         => false,
            'allow_partial_cancel' => false,
            'allow_overlap_release'=> false,
            'min_duration_minutes' => 30,
            'max_duration_minutes' => 480,
            'allowed_days'         => ['saturday','sunday','monday','tuesday','wednesday'],
            'allowed_hours'        => ['start' => '08:00', 'end' => '18:00'],
            'requires_approval'    => false,
        ];

        foreach (['seat', 'spot', 'car', 'meeting'] as $type) {
            foreach ($defaults as $key => $value) {
                ReservationPolicy::updateOrCreate(
                    ['resource_type' => $type, 'key' => $key],
                    ['value' => $value]
                );
            }
        }
    }
}
