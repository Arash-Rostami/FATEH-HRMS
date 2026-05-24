<?php

namespace Database\Factories;

use App\Models\Read;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReadFactory extends Factory
{
    protected $model = Read::class;

    public function definition(): array
    {
        return [
            'document_id' => fake()->numberBetween(1, 50),
            'user_id' => fake()->numberBetween(1, 50),
            'read' => fake()->boolean(),
            'read_count' => fake()->numberBetween(1, 100),
            'combined_read_count' => fake()->numberBetween(1, 100),
        ];
    }
}
