<?php

namespace Database\Factories;

use App\Models\DMS;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DMSFactory extends Factory
{
    protected $model = DMS::class;

    public function definition(): array
    {
        return [
            'file' => fake()->word(),
            'code' => fake()->unique()->numerify('DOC-####'),
            'version' => fake()->numerify('1.#'),
            'title' => fake()->sentence(),
            'status' => fake()->randomElement(['live', 'under_review', 'obsolete']),
            'type' => true,
            'owners' => [fake()->numberBetween(1, 50)],
            'users' => [fake()->numberBetween(1, 50)],
            'revision' => fake()->paragraph(),
            'combined_read_count' => fake()->numberBetween(0, 100),
            'extra' => [],
            'tags' => [],
        ];
    }
}
