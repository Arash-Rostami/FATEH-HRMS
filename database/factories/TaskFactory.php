<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in-progress', 'done']),
            'deadline' => '',
            'user_id' => fake()->numberBetween(1, 50),
            'assigned_to' => fake()->numberBetween(1, 100),
        ];
    }
}
