<?php

namespace Database\Factories;

use App\Models\Reaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'feed_id' => fake()->numberBetween(1, 50),
            'emoji' => fake()->word(),
        ];
    }
}
