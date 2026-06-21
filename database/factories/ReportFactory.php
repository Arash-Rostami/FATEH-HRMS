<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'cover_image' => fake()->imageUrl(),
            'department_id' => Department::inRandomOrder()->value('code') ?? Department::factory()->create()->code,
            'file_path' => fake()->word(),
        ];
    }
}
