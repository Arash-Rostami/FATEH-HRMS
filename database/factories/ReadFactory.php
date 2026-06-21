<?php

namespace Database\Factories;

use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadFactory extends Factory
{
    protected $model = Read::class;

    public function definition(): array
    {
        return [
            'document_id' => DMS::inRandomOrder()->value('id') ?? DMS::factory(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'read' => true,
            'read_count' => fake()->numberBetween(0, 100),
            'combined_read_count' => 0,
        ];
    }
}
