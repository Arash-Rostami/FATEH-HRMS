<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;


class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
            'is_ghost' => false,
        ]);
    }

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'name_en' => $this->faker->optional()->words(2, true),
            'category' => $this->faker->randomElement([
                'Frontend',
                'Backend',
                'DevOps',
                'Design',
                'Data',
                'Soft Skills',
                'Other',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'icon' => $this->faker->optional()->randomElement([
                'code',
                'server',
                'palette',
                'database',
                'users',
                'cog',
            ]),
            'is_active' => true,
            'is_ghost' => false,
            'search_count' => $this->faker->numberBetween(0, 500),
            'last_searched_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function ghost(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
            'is_ghost' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
