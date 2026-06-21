<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['todo', 'in-progress', 'done']),
            'deadline' => now()->addDays(fake()->numberBetween(1, 10)),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'assigned_to' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
