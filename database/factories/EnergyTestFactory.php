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
        $mind = fake()->numberBetween(0, 4);
        $emotion = fake()->numberBetween(0, 4);
        $physique = fake()->numberBetween(0, 4);
        $soul = fake()->numberBetween(0, 4);

        return [
            'user_id' => User::factory(),
            'mind_score' => $mind,
            'emotion_score' => $emotion,
            'physique_score' => $physique,
            'soul_score' => $soul,
            'overall_score' => $mind + $emotion + $physique + $soul,
            'answers' => [],
            'month_index' => fake()->numberBetween(0, 11),
            'completed_at' => now(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn() => ['user_id' => $user->id]);
    }

    public function completedDaysAgo(int $days): static
    {
        return $this->state(fn() => ['completed_at' => now()->subDays($days)]);
    }
}