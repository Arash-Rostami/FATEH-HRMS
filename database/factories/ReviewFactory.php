<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'comments' => fake()->paragraph(),
            'actions' => fake()->paragraph(),
            'feedback' => fake()->randomElement(['agree', 'neutral', 'disagree', 'incomplete', 'unknown']),
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code,
            'complete' => fake()->boolean(),
            'referral' => [],
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'suggestion_id' => Suggestion::inRandomOrder()->value('id') ?? Suggestion::factory(),
        ];
    }
}
