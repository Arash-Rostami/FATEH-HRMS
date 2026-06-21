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
            'document_id' => \App\Models\DMS::inRandomOrder()->value('id') ?? \App\Models\DMS::factory(),
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'read' => true,
            'read_count' => fake()->numberBetween(0, 100),
            'combined_read_count' => 0,
        ];
    }
}
