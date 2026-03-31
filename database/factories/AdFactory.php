<?php

namespace Database\Factories;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'position' => fake()->jobTitle(),
            'certificate' => fake()->randomElement(['Bachelor', 'Master', 'PhD']) . ' in ' . fake()->word(),
            'skill' => implode(', ', fake()->words(3)),
            'experience' => fake()->numberBetween(1, 10) . ' years',
            'gender' => fake()->randomElement(['Male', 'Female', 'Any']),
            'link' => fake()->url(),
            'active' => fake()->boolean(80),
        ];
    }
}
