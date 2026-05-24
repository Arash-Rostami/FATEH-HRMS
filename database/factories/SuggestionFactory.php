<?php

namespace Database\Factories;

use App\Models\Suggestion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class SuggestionFactory extends Factory
{
    protected $model = Suggestion::class;

    public function definition(): array
    {
        return [
            'title' => fake()->paragraph(),
            'description' => fake()->paragraph(),
            'departments' => [],
            'purpose' => [],
            'rule' => [],
            'attachment' => fake()->paragraph(),
            'stage' => fake()->word(),
            'self_fill' => fake()->boolean(),
            'abort' => fake()->boolean(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'comments' => fake()->paragraph(),
            'user_id' => fake()->numberBetween(1, 50),
        ];
    }
}
