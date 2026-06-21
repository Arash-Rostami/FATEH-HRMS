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
            'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'complete' => fake()->boolean(),
            'referral' => [],
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'suggestion_id' => \App\Models\Suggestion::inRandomOrder()->value('id') ?? \App\Models\Suggestion::factory(),
        ];
    }
}
