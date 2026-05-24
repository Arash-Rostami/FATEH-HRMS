<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 50),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'cover_image' => fake()->imageUrl(),
            'department_id' => fake()->word(),
            'file_path' => fake()->word(),
            'active' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
