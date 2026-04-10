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
        $feedback = $this->faker->randomElement(array_keys(Review::FEEDBACKS));

        return [
            'comments' => $this->faker->optional()->paragraphs(2, true),
            'actions' => $this->faker->optional()->paragraphs(2, true),
            'feedback' => $feedback,
            'department_id' => Department::factory()->create()->code,
            'complete' => $this->faker->boolean(70),
            'referral' => $this->faker->optional()->randomElement([
                [
                    'source' => $this->faker->randomElement(['manager', 'self', 'team']),
                    'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                    'note' => $this->faker->sentence(),
                ],
                null,
            ]),
            'user_id' => User::factory(),
            'suggestion_id' => Suggestion::factory(),
        ];
    }

    public function agreed(): static
    {
        return $this->state(fn () => ['feedback' => 'agree']);
    }

    public function disagreed(): static
    {
        return $this->state(fn () => ['feedback' => 'disagree']);
    }

    public function incomplete(): static
    {
        return $this->state(fn () => ['feedback' => 'incomplete']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['complete' => true]);
    }
}
