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
            'sender_id'    => User::factory(),
            'recipient_id' => User::factory(),
            'body'         => fake()->paragraph(),
            'reply_to_id'  => null,
            'is_edited'    => false,
            'read_at'      => fake()->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'created_at'   => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at'   => fn(array $attributes) => $attributes['created_at'],
        ];
    }

    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_edited'  => true,
            'updated_at' => fake()->dateTimeBetween($attributes['created_at'], 'now'),
        ]);
    }

    public function reply(Message $parent): static
    {
        return $this->state(fn(array $attributes) => [
            'sender_id'    => $parent->recipient_id,
            'recipient_id' => $parent->sender_id,
            'reply_to_id'  => $parent->id,
            'created_at'   => fake()->dateTimeBetween($parent->created_at, 'now'),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
