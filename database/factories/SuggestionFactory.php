<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuggestionFactory extends Factory
{
    protected $model = Suggestion::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'departments' => $this->randomDepartmentCodes(),
            'purpose' => $this->randomSubset(array_keys(Suggestion::PURPOSES)),
            'rule' => $this->randomSubset(array_keys(Suggestion::RULES)),
            'attachment' => fake()->boolean(25) ? fake()->filePath() : null,
            'stage' => fake()->randomElement(array_keys(Suggestion::STAGES)),
            'self_fill' => fake()->boolean(30),
            'abort' => false,
            'priority' => fake()->randomElement(array_keys(Suggestion::PRIORITIES)),
            'comments' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    protected function randomDepartmentCodes(): array
    {
        $codes = Department::query()->pluck('code')->filter()->values()->all();

        if (empty($codes)) return [];

        $count = fake()->numberBetween(1, min(3, count($codes)));

        return fake()->randomElements($codes, $count);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['stage' => 'pending']);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['stage' => 'accepted']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['stage' => 'rejected']);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['stage' => 'closed']);
    }

    protected function randomSubset(array $items): array
    {
        $count = fake()->numberBetween(1, count($items));
        return fake()->randomElements($items, $count);
    }
}
