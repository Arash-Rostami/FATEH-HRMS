<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition()
    {
        return [
            'requester_id' => User::factory()->create()->id,
            'request_type' => $this->faker->randomElement(['support', 'access']),
            'request_area' => $this->faker->word,
            'request_subject' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'attachment' => $this->faker->optional()->url,
            'additional_notes' => $this->faker->text,
            'assigned_to' => User::factory()->create()->id,
            'completion_deadline' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'completion_date' => $this->faker->optional()->dateTimeBetween('+3 days', '+1 month'),
            'action_result' => $this->faker->text,
            'status' => $this->faker->randomElement(['open', 'closed', 'in-progress']),
            'effectiveness' => $this->faker->word,
            'satisfaction_score' => $this->faker->numberBetween(0, 5),
            'requester_files' => json_encode([$this->faker->url]),
            'assignee_files' => json_encode([$this->faker->url]),
            'extra' => json_encode(['note' => $this->faker->sentence]),
        ];
    }
}
