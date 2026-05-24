<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'comments' => fake()->paragraph(),
            'actions' => fake()->paragraph(),
            'feedback' => fake()->randomElement(['agree', 'neutral', 'disagree', 'incomplete', 'unknown']),
            'department_id' => fake()->paragraph(),
            'complete' => fake()->boolean(),
            'referral' => [],
            'user_id' => fake()->numberBetween(1, 50),
            'suggestion_id' => fake()->numberBetween(1, 50),
        ];
    }
}
