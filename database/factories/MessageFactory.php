<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'sender_id' => fake()->numberBetween(1, 50),
            'recipient_id' => fake()->numberBetween(1, 50),
            'body' => fake()->paragraph(),
            'reply_to_id' => fake()->numberBetween(1, 50),
            'is_edited' => fake()->boolean(),
            'read_at' => now(),
        ];
    }
}
