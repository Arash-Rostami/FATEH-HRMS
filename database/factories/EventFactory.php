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
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'date' => fake()->date(),
            'private' => fake()->boolean(),


        ];
    }
}
