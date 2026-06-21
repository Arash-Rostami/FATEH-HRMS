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
            'owners' => [(Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code) ?? 'ALL'],
            'users' => array_filter([(string)(User::inRandomOrder()->value('id') ?? User::factory()->create()->id)]),
            'revision' => fake()->paragraph(),
            'combined_read_count' => 0,
            'extra' => [],
            'tags' => [],
        ];
    }
}
