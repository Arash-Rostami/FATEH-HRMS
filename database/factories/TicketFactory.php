<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'requester_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'request_type' => fake()->word(),
            'request_area' => fake()->word(),
            'request_subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->word(),
            'attachment' => fake()->word(),
            'additional_notes' => fake()->paragraph(),
            'assigned_to' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
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
