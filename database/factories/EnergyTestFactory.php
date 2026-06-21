<?php

namespace Database\Factories;

use App\Models\EnergyTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnergyTestFactory extends Factory
{
    protected $model = EnergyTest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'mind_score' => fake()->numberBetween(1, 100),
            'emotion_score' => fake()->numberBetween(1, 100),
            'physique_score' => fake()->numberBetween(1, 100),
            'soul_score' => fake()->numberBetween(1, 100),
            'overall_score' => fake()->numberBetween(1, 100),
            'answers' => [],
            'month_index' => fake()->numberBetween(1, 100),
            'completed_at' => now(),
        ];
    }
}
