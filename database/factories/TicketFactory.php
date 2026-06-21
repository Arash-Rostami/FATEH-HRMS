<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'requester_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'request_type' => fake()->word(),
            'request_area' => fake()->word(),
            'request_subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->word(),
            'attachment' => fake()->word(),
            'additional_notes' => fake()->paragraph(),
            'assigned_to' => User::inRandomOrder()->value('id') ?? User::factory(),
            'completion_deadline' => now(),
            'completion_date' => now(),
            'action_result' => fake()->paragraph(),
            'status' => fake()->word(),
            'effectiveness' => fake()->word(),
            'satisfaction_score' => fake()->numberBetween(1, 5),
            'requester_files' => [],
            'assignee_files' => [],
            'extra' => [],
        ];
    }
}
