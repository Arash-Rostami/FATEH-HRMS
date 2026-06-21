<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'date' => now()->addDays(fake()->numberBetween(1, 10)),
            'private' => fake()->boolean(),

        ];
    }
}
