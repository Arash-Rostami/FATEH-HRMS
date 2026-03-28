<?php

namespace Database\Factories;

use App\Models\ReservationPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationPolicyFactory extends Factory
{
    protected $model = ReservationPolicy::class;

    public function definition(): array
    {
        return [
            'resource_type' => $this->faker->randomElement(['room', 'equipment', 'vehicle']),
            'booking_window_days' => $this->faker->numberBetween(1, 30),
            'booking_window_hours' => $this->faker->numberBetween(1, 24),
        ];
    }
}
