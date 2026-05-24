<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'date' => '',
            'private' => fake()->boolean(),
            'date_jalali' => fake()->word(),
            'date_time_part' => fake()->word(),
        ];
    }
}
