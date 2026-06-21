<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DMS;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DMSFactory extends Factory
{
    protected $model = DMS::class;

    public function definition(): array
    {
        return [
            'file' => 'dms/' . fake()->unique()->slug() . '.pdf',
            'code' => fake()->unique()->numerify('DOC-####'),
            'version' => fake()->numerify('1.#'),
            'title' => fake()->sentence(),
            'status' => fake()->randomElement(['live', 'under_review', 'obsolete']),
            'type' => fake()->boolean(),
            'owners' => [Department::inRandomOrder()->value('code') ?? 'ALL'],
            'users' => array_filter([User::inRandomOrder()->value('id')]),
            'revision' => fake()->paragraph(),
            'combined_read_count' => fake()->numberBetween(0, 100),
            'extra' => [],
            'tags' => [],
        ];
    }
}
