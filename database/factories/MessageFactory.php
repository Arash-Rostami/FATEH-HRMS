<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'recipient_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'body' => fake()->paragraph(),
            'reply_to_id' => null,
            'is_edited' => fake()->boolean(),
            'read_at' => now(),
        ];
    }
}
