<?php

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => fake()->word(),
            'metadata' => [],
            'status' => fake()->word(),
            'image' => fake()->imageUrl(),
        ];
    }
}
