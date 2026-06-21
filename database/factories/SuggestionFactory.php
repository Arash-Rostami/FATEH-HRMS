<?php

namespace Database\Factories;

use App\Models\Suggestion;
use App\Models\User;
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
            'stage' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'self_fill' => fake()->boolean(),
            'abort' => fake()->boolean(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'comments' => fake()->paragraph(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
