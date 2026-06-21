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
            'deadline' => now()->addDays(fake()->numberBetween(1, 10)),
            'user_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'assigned_to' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
        ];
    }
}
