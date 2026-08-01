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
            'request_type' => fake()->randomElement(['support', 'access', 'development']),
            'request_area' => null,
            'request_subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'attachment' => null,
            'additional_notes' => null,
            'assigned_to' => null,
            'completion_deadline' => null,
            'completion_date' => null,
            'action_result' => null,
            'status' => 'open',
            'effectiveness' => null,
            'satisfaction_score' => null,
            'requester_files' => [],
            'assignee_files' => [],
            'extra' => [],
        ];
    }
}
