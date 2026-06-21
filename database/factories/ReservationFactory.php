<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        // Null start_time/end_time for full-day reservations
        $isFullDay = fake()->boolean(20);
        return [
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'resource_id' => \App\Models\Resource::inRandomOrder()->value('id') ?? \App\Models\Resource::factory(),
            'start_time' => $isFullDay ? null : now()->addDays(fake()->numberBetween(1, 10))->setHour(9),
            'end_time' => $isFullDay ? null : now()->addDays(fake()->numberBetween(1, 10))->setHour(10),
            'is_full_day' => $isFullDay,
            'status' => fake()->randomElement(['active', 'cancelled', 'completed']),
            'cancelled_by_id' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'parent_id' => null,
        ];
    }
}
