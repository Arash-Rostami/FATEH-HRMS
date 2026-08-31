<?php

namespace Database\Factories;

use App\Enums\TaskActivityType;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReplyFactory extends Factory
{
    protected $model = Reply::class;

    public function definition(): array
    {
        return [
            'repliable_type' => Task::class,
            'repliable_id' => Task::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->paragraph(),
            'files' => [],
            'type' => $this->faker->randomElement(TaskActivityType::cases()),
            'payload' => [],
        ];
    }
}
