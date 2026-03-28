<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $startTime = Carbon::instance($this->faker->dateTimeBetween('now', '+1 month'));
        $endTime = (clone $startTime)->addHours($this->faker->numberBetween(1, 8));

        return [
            'user_id' => User::factory(),
            'resource_id' => Resource::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $this->faker->randomElement(['pending', 'approved', 'completed']),
            'cancelled_by_id' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'parent_id' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by_id' => User::factory(),
            'cancelled_at' => now(),
            'cancel_reason' => $this->faker->sentence(),
        ]);
    }

    public function withParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Reservation::factory(),
        ]);
    }
}
