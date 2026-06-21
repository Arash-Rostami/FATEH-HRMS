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
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'cover_image' => fake()->imageUrl(),
            'department_id' => \App\Models\Department::inRandomOrder()->value('code') ?? \App\Models\Department::factory()->create()->code,
            'file_path' => fake()->word(),
        ];
    }
}
