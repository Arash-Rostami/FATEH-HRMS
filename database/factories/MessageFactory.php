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
            'sender_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'recipient_id' => \App\Models\User::inRandomOrder()->value('id') ?? \App\Models\User::factory(),
            'body' => fake()->paragraph(),
            'reply_to_id' => null,
            'is_edited' => fake()->boolean(),
            'read_at' => now(),
        ];
    }
}
