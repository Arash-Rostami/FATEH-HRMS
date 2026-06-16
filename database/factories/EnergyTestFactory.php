<?php

namespace Database\Factories;

use App\Models\EnergyTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnergyTestFactory extends Factory
{
    protected $model = EnergyTest::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'mind_score' => '',
            'emotion_score' => '',
            'physique_score' => '',
            'soul_score' => '',
            'overall_score' => '',
            'answers' => [],
            'month_index' => '',
            'completed_at' => now(),
        ];
    }
}
