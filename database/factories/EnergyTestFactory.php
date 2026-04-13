<?php

namespace Database\Factories;

use App\Models\EnergyTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnergyTest>
 */
class EnergyTestFactory extends Factory
{
    protected $model = EnergyTest::class;

    public function definition(): array
    {
        $mind = fake()->numberBetween(1, 10);
        $emotion = fake()->numberBetween(1, 10);
        $physique = fake()->numberBetween(1, 10);
        $soul = fake()->numberBetween(1, 10);
        $overall = (int) round(($mind + $emotion + $physique + $soul) / 4);
        $completedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => User::factory(),
            'mind_score' => $mind,
            'emotion_score' => $emotion,
            'physique_score' => $physique,
            'soul_score' => $soul,
            'overall_score' => $overall,
            'answers' => [
                'mind' => fake()->sentences(3, true),
                'emotion' => fake()->sentences(3, true),
                'physique' => fake()->sentences(3, true),
                'soul' => fake()->sentences(3, true),
            ],
            'month_index' => (int) (new \DateTime($completedAt))->format('n'), // 1-12
            'completed_at' => $completedAt,
        ];
    }

    public function withScores(int $mind, int $emotion, int $physique, int $soul): static
    {
        return $this->state(fn (array $attributes) => [
            'mind_score' => $mind,
            'emotion_score' => $emotion,
            'physique_score' => $physique,
            'soul_score' => $soul,
            'overall_score' => (int) round(($mind + $emotion + $physique + $soul) / 4),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forMonth(int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'month_index' => $month,
            'completed_at' => fake()->dateTimeBetween(
                now()->setMonth($month)->startOfMonth(),
                now()->setMonth($month)->endOfMonth()
            ),
        ]);
    }

    public function highEnergy(): static
    {
        return $this->state(fn (array $attributes) => [
            'mind_score' => fake()->numberBetween(8, 10),
            'emotion_score' => fake()->numberBetween(8, 10),
            'physique_score' => fake()->numberBetween(8, 10),
            'soul_score' => fake()->numberBetween(8, 10),
            'overall_score' => fake()->numberBetween(8, 10),
        ]);
    }

    public function lowEnergy(): static
    {
        return $this->state(fn (array $attributes) => [
            'mind_score' => fake()->numberBetween(1, 3),
            'emotion_score' => fake()->numberBetween(1, 3),
            'physique_score' => fake()->numberBetween(1, 3),
            'soul_score' => fake()->numberBetween(1, 3),
            'overall_score' => fake()->numberBetween(1, 3),
        ]);
    }
}
